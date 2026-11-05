<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    // Only explicit DTO arrays enter this resource; never serialize whole models.
    public function toArray(Request $request): array
    {
        return $this->resource['payload'];
    }

    public function with(Request $request): array
    {
        return ['meta' => $this->resource['meta']];
    }
}
