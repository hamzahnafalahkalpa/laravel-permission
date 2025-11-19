<?php

namespace Hanafalah\LaravelPermission\Resources\Role;

use Hanafalah\LaravelSupport\Resources\ApiResource;

class ViewRole extends ApiResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray(\Illuminate\Http\Request $request): array
  {
    if (isset(request()->childs) && request()->childs) {
      $this->load('childs');
    }
    $arr = [
      'id'         => $this->id,
      'name'       => $this->name,
      'childs'     => $this->relationValidation('childs', function () {
        $childs = $this->childs;
        return $childs->transform(function ($child) {
          return $child->toViewApi()->resolve();
        });
      }),
      'permission_ids' => $this->permission_ids,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at
    ];
    if (isset($this->current)) $arr['current'] = $this->current;

    return $arr;
  }
}
