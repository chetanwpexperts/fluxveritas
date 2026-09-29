<?php

namespace App\Services\Outy\Tools;

use App\Models\User;
use App\Services\ModuleService;
use App\Services\Outy\ToolDeniedException;

/**
 * One capability Outy can use. Subclasses declare who may use them
 * ($roles / $permission / $module) and implement handle().
 *
 * Callers must go through run(): it re-checks access for the given user and
 * drops any argument not declared in parameters() — so a model (or a prompt
 * injection) can never pass a user_id or organization_id the tool didn't ask for.
 * handle() must scope every query to $user->organization_id / $user->id.
 */
abstract class OutyTool
{
    /** Roles allowed to use the tool (empty = any role). */
    protected array $roles = [];

    /** Spatie permission required, if any. */
    protected ?string $permission = null;

    /** Module that must be enabled for the user's organization, if any. */
    protected ?string $module = null;

    /** Whether the user must belong to an organization. */
    protected bool $requiresOrganization = true;

    abstract public function name(): string;

    abstract public function description(): string;

    /** @return array<string, mixed> tool result, JSON-serialisable */
    abstract protected function handle(User $user, array $args): array;

    /** JSON schema for the arguments (an object). */
    public function parameters(): array
    {
        return ['type' => 'object', 'properties' => new \stdClass(), 'additionalProperties' => false];
    }

    public function allows(User $user): bool
    {
        if ($this->requiresOrganization && !$user->organization_id) {
            return false;
        }
        if ($this->roles && !$user->hasAnyRole($this->roles)) {
            return false;
        }
        if ($this->permission && !$user->can($this->permission)) {
            return false;
        }
        if ($this->module && !$user->hasRole('super_admin')
            && !app(ModuleService::class)->hasModule($user->organization_id, $this->module)) {
            return false;
        }

        return true;
    }

    /** Checks access again, keeps only declared arguments, then runs the tool. */
    final public function run(User $user, array $args): array
    {
        if (!$this->allows($user)) {
            throw new ToolDeniedException("{$this->name()} is not available to this user.");
        }

        $declared = array_keys((array) ($this->parameters()['properties'] ?? []));

        return $this->handle($user, array_intersect_key($args, array_flip($declared)));
    }

    /** OpenAI "tools" entry. */
    public function schema(): array
    {
        return [
            'type'     => 'function',
            'function' => [
                'name'        => $this->name(),
                'description' => $this->description(),
                'parameters'  => $this->parameters(),
            ],
        ];
    }

    /** Clamp an integer argument into a range. */
    protected function intArg(array $args, string $key, int $default, int $min, int $max): int
    {
        $value = filter_var($args[$key] ?? $default, FILTER_VALIDATE_INT);

        return max($min, min($max, $value === false ? $default : $value));
    }
}
