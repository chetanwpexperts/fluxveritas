<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentDownload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    private function isHr(): bool
    {
        return auth()->user()->hasAnyRole(['hr', 'admin', 'owner', 'super_admin']);
    }

    public function index(Request $request)
    {
        $user  = auth()->user();
        $isHr  = $this->isHr();
        $orgId = $user->organization_id;

        $query = Document::visibleTo($user)
            ->with(['uploader', 'department', 'employee'])
            ->where('is_active', true);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title', 'LIKE', "%{$s}%")
                                      ->orWhere('description', 'LIKE', "%{$s}%"));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($isHr && $request->filled('audience')) {
            $query->where('audience', $request->audience);
        }

        $stats = [];
        if ($isHr) {
            $base = Document::where('organization_id', $orgId)->where('is_active', true);
            $stats = [
                'total'      => $base->count(),
                'personal'   => (clone $base)->where('audience', 'personal')->count(),
                'department' => (clone $base)->where('audience', 'department')->count(),
                'org'        => (clone $base)->where('audience', 'org')->count(),
            ];
        }

        $documents = $query->latest()->paginate(20)->withQueryString();

        return view('documents.index', compact('documents', 'isHr', 'stats'));
    }

    public function create()
    {
        abort_unless($this->isHr(), 403);

        $orgId       = auth()->user()->organization_id;
        $departments = Department::where('organization_id', $orgId)->orderBy('name')->get();
        $employees   = User::where('organization_id', $orgId)
                           ->where('is_active', true)
                           ->whereDoesntHave('roles', fn($q) => $q->where('name', 'super_admin'))
                           ->orderBy('name')
                           ->get();

        $categories = [
            'policy'            => 'Company Policy',
            'handbook'          => 'Employee Handbook',
            'offer_letter'      => 'Offer Letter',
            'appraisal'         => 'Appraisal Letter',
            'salary_slip'       => 'Salary Slip',
            'experience_letter' => 'Experience Letter',
            'nda'               => 'NDA',
            'other'             => 'Other',
        ];

        return view('documents.create', compact('departments', 'employees', 'categories'));
    }

    public function store(Request $request)
    {
        abort_unless($this->isHr(), 403);

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string|max:1000',
            'category'      => 'required|in:policy,handbook,offer_letter,appraisal,salary_slip,experience_letter,nda,other',
            'audience'      => 'required|in:org,department,personal',
            'department_id' => 'required_if:audience,department|nullable|exists:departments,id',
            'employee_id'   => 'required_if:audience,personal|nullable|exists:users,id',
            'document'      => 'required|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
            'version'       => 'nullable|string|max:20',
        ]);

        $user  = auth()->user();
        $orgId = $user->organization_id;
        $file  = $request->file('document');
        $ext   = $file->getClientOriginalExtension();
        $slug  = Str::slug($data['title']);
        $path  = $file->storeAs(
            "documents/{$orgId}/{$data['audience']}",
            time() . '_' . $slug . '.' . $ext,
            'local'
        );

        Document::create([
            'organization_id' => $orgId,
            'uploaded_by'     => $user->id,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'category'        => $data['category'],
            'audience'        => $data['audience'],
            'department_id'   => $data['audience'] === 'department' ? $data['department_id'] : null,
            'employee_id'     => $data['audience'] === 'personal'   ? $data['employee_id']   : null,
            'file_path'       => $path,
            'file_name'       => $file->getClientOriginalName(),
            'file_size'       => $file->getSize(),
            'file_type'       => $file->getMimeType(),
            'version'         => $data['version'] ?? '1.0',
        ]);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }

    public function show(Document $document)
    {
        $user = auth()->user();
        abort_unless(
            Document::visibleTo($user)->where('id', $document->id)->where('is_active', true)->exists(),
            403
        );

        $document->load(['uploader', 'department', 'employee']);
        $isHr     = $this->isHr();
        $downloads = $isHr ? $document->downloads()->with('user')->latest('downloaded_at')->limit(20)->get() : collect();

        return view('documents.show', compact('document', 'isHr', 'downloads'));
    }

    public function download(Document $document)
    {
        $user = auth()->user();
        abort_unless(
            Document::visibleTo($user)->where('id', $document->id)->where('is_active', true)->exists(),
            403
        );

        $document->increment('download_count');
        DocumentDownload::create([
            'document_id'   => $document->id,
            'user_id'       => $user->id,
            'downloaded_at' => now(),
            'ip_address'    => request()->ip(),
        ]);

        return Storage::disk('local')->download($document->file_path, $document->file_name);
    }

    public function destroy(Document $document)
    {
        abort_unless($this->isHr(), 403);
        abort_unless($document->organization_id === auth()->user()->organization_id, 403);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted.');
    }

    public function updateVersion(Request $request, Document $document)
    {
        abort_unless($this->isHr(), 403);
        abort_unless($document->organization_id === auth()->user()->organization_id, 403);

        $data = $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:10240',
            'version'  => 'nullable|string|max:20',
        ]);

        Storage::disk('local')->delete($document->file_path);

        $file = $request->file('document');
        $ext  = $file->getClientOriginalExtension();
        $slug = Str::slug($document->title);
        $path = $file->storeAs(
            "documents/{$document->organization_id}/{$document->audience}",
            time() . '_' . $slug . '.' . $ext,
            'local'
        );

        $document->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => $file->getMimeType(),
            'version'   => $data['version'] ?? $document->version,
        ]);

        return redirect()->route('documents.show', $document)->with('success', 'Document version updated.');
    }
}
