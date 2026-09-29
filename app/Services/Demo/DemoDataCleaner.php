<?php

namespace App\Services\Demo;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Removes data belonging to demo organizations (is_demo = true) — and only those.
 *
 * Every Eloquent model in app/Models is checked for an organization_id,
 * task_id or user_id column, so new modules are cleaned up without having
 * to be listed here. Rows without an organization column (task comments,
 * announcement reads, profiles…) are removed through their task or user.
 */
class DemoDataCleaner
{
    /** @var Collection<class-string<Model>, string>|null model class => table */
    private ?Collection $models = null;

    private array $columns = [];

    /**
     * Empty the organizations but keep the organization rows and their users,
     * so the seeder can rebuild fresh data with the same logins.
     */
    public function clearContent(iterable $orgIds): void
    {
        $orgIds = $this->demoOrgIds($orgIds);
        if (!$orgIds) {
            return;
        }

        DB::transaction(function () use ($orgIds) {
            $userIds = User::whereIn('organization_id', $orgIds)->pluck('id')->all();

            // Unlink people from teams, departments and managers before those are removed
            User::whereIn('id', $userIds)->update([
                'team_id'              => null,
                'department_id'        => null,
                'reporting_manager_id' => null,
            ]);

            $projectIds = Project::withoutGlobalScopes()->whereIn('organization_id', $orgIds)->pluck('id')->all();
            $taskIds    = Task::whereIn('project_id', $projectIds)->pluck('id')->all();

            $this->deleteFrom($this->modelsWith('task_id'), 'task_id', $taskIds);
            Task::whereIn('id', $taskIds)->delete();

            // Per-user rows in tables that have no organization column of their own
            $userOnly = $this->modelsWith('user_id')->reject(fn ($table) => $this->hasColumn($table, 'organization_id'));
            $this->deleteFrom($userOnly, 'user_id', $userIds);

            $this->deleteFrom($this->modelsWith('organization_id'), 'organization_id', $orgIds);
        });
    }

    /**
     * Delete the organizations and everything linked to them, including users.
     *
     * @return array{organizations:int, users:int}
     */
    public function purge(iterable $orgIds): array
    {
        $orgIds = $this->demoOrgIds($orgIds);
        if (!$orgIds) {
            return ['organizations' => 0, 'users' => 0];
        }

        return DB::transaction(function () use ($orgIds) {
            $this->clearContent($orgIds);

            $users  = User::whereIn('organization_id', $orgIds)->get();
            $emails = $users->pluck('email')->all();

            DB::table('sessions')->whereIn('user_id', $users->pluck('id'))->delete();
            if (Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->whereIn('email', $emails)->delete();
            }

            // One by one so Spatie detaches roles and permissions
            $users->each->delete();

            $count = Organization::whereIn('id', $orgIds)->delete();

            return ['organizations' => $count, 'users' => $users->count()];
        });
    }

    /** Only ids of organizations flagged is_demo; refuses anything else. */
    private function demoOrgIds(iterable $orgIds): array
    {
        $ids = collect($orgIds)->map(fn ($id) => (int) $id)->unique()->values()->all();
        if (!$ids) {
            return [];
        }

        $notDemo = Organization::whereIn('id', $ids)->where('is_demo', false)->pluck('name');
        if ($notDemo->isNotEmpty()) {
            throw new RuntimeException('Refusing to touch non-demo organizations: ' . $notDemo->implode(', '));
        }

        return $ids;
    }

    /**
     * Deletes matching rows model by model. Tables that reference each other are
     * retried in later passes until nothing is left, so no fixed order is needed.
     */
    private function deleteFrom(Collection $models, string $column, array $ids): void
    {
        if (!$ids || $models->isEmpty()) {
            return;
        }

        $pending = $models->keys()->all();

        for ($pass = 0; $pending && $pass < 5; $pass++) {
            $failed = [];
            foreach ($pending as $class) {
                try {
                    $class::query()->withoutGlobalScopes()->whereIn($column, $ids)->delete();
                } catch (QueryException $e) {
                    $failed[] = $class;
                    $lastError = $e;
                }
            }

            if (count($failed) === count($pending)) {
                throw $lastError;
            }
            $pending = $failed;
        }
    }

    /** @return Collection<class-string<Model>, string> models whose table has $column */
    private function modelsWith(string $column): Collection
    {
        return $this->models()->filter(fn ($table) => $this->hasColumn($table, $column));
    }

    private function models(): Collection
    {
        return $this->models ??= collect(File::files(app_path('Models')))
            ->map(fn ($file) => 'App\\Models\\' . $file->getFilenameWithoutExtension())
            ->filter(fn ($class) => class_exists($class)
                && is_subclass_of($class, Model::class)
                && !(new \ReflectionClass($class))->isAbstract())
            ->reject(fn ($class) => in_array($class, [Organization::class, User::class], true))
            ->mapWithKeys(fn ($class) => [$class => (new $class)->getTable()])
            ->filter(fn ($table) => Schema::hasTable($table));
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns[$table] ??= Schema::getColumnListing($table), true);
    }
}
