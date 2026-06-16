<?php

namespace App\Http\Requests;

use App\Enums\Severity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoreScanResultRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'schema_version' => ['required', 'integer'],

            'meta' => ['required', 'array'],
            'meta.service' => ['required', 'string'],
            'meta.repository_url' => ['required', 'url'],
            'meta.branch' => ['required', 'string'],
            'meta.environment' => ['required', 'string'],
            'meta.repository' => ['required', 'string'],
            'meta.commit_hash' => ['required', 'string'],
            'meta.actor' => ['required', 'string'],
            'meta.timestamp' => ['required', 'date'],
            'meta.tier' => ['required', 'string'],
            'meta.image_ref' => ['nullable', 'string'],
            'meta.pr_number' => ['nullable', 'integer'],

            'runs' => ['required', 'array', 'min:1'],
            'runs.*.tool' => ['required', 'array'],
            'runs.*.tool.key' => [
                'required', 'string',
            ],
            'runs.*.tool.category' => ['required', 'string', 'in:SCA,SECRETS,SAST,IaC'],
            'runs.*.tool.version' => ['nullable', 'string'],

            'runs.*.scan' => ['required', 'array'],
            'runs.*.scan.type' => [
                'required',
                'string',
                'in:filesystem,repository,source,infrastructure,container_image',
            ],
            'runs.*.scan.status' => ['required', 'string', 'in:success,missing'],
            'runs.*.scan.artifact_ref' => ['required', 'string'],

            'runs.*.findings' => ['present', 'array'],
            'runs.*.findings.*.type' => [
                'required',
                'string',
                'in:vulnerability,secret,code_issue,iac_misconfiguration',
            ],
            'runs.*.findings.*.severity' => [
                'required',
                'string',
                'in:'.implode(',', Severity::ingestValues()),
            ],
            'runs.*.findings.*.tool_severity' => ['required', 'string'],
            'runs.*.findings.*.reference_id' => ['required', 'string'],
            'runs.*.findings.*.title' => ['required', 'string'],
            'runs.*.findings.*.description' => ['nullable', 'string'],
            'runs.*.findings.*.fingerprint' => ['required', 'string'],
            'runs.*.findings.*.first_seen_at' => ['required', 'date'],
            'runs.*.findings.*.details' => ['nullable', 'array'],
        ];
    }

    #[\Override]
    public function messages(): array
    {
        return [
            'meta.repository_url.required' => 'Missing meta.repository_url',
            'meta.repository_url.url' => 'meta.repository_url must be a valid URL',
            'runs.*.findings.*.severity.in' => 'Severity must be one of: '
                .implode(', ', Severity::ingestValues()),
        ];
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        if ($this->header('Content-Length') > 10 * 1024 * 1024) {
            throw new HttpException(413, 'Payload too large');
        }

        $this->merge($this->json()->all());
    }
}
