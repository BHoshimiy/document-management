<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $documents) {}

    public function store(StoreDocumentRequest $request, DocumentFolder $folder): RedirectResponse
    {
        // Role decides the branch, never the payload: a client's posted
        // company_id is never read, so it cannot target someone else's company.
        $company = $request->user()->canManageCatalog()
            ? Company::findOrFail($request->integer('company_id'))
            : $request->user()->company;

        abort_if($company === null, 403, __('documents.no_company'));
        $this->authorize('update', $company);

        $this->documents->store($company, $folder, $request->file('file'), [
            'category_id' => $request->integer('category_id'),
            'name' => $request->string('name')->toString() ?: null,
        ]);

        return back()->with('status', __('documents.uploaded'));
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('view', $document);

        abort_unless($this->documents->exists($document), 404);

        return $this->documents->stream($document);
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $this->documents->delete($document);

        return back()->with('status', __('documents.deleted'));
    }
}
