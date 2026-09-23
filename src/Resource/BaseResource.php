<?php

namespace Witify\Support\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'can' => $this->canDo(),
        ]);
    }

    /**
     * Returns the permissions of the resource.
     *
     * @return array<string, bool>
     */
    protected function canDo(): array
    {
        return [
            'view' => Gate::allows('view', $this->resource),
            'update' => Gate::allows('update', $this->resource),
            'delete' => Gate::allows('delete', $this->resource),
        ];
    }
}
