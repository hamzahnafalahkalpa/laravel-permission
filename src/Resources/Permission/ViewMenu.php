<?php

namespace Hanafalah\LaravelPermission\Resources\Permission;

use Hanafalah\LaravelSupport\Resources\ApiResource;

class ViewMenu extends ApiResource
{
  /**
   * Transform the resource into an array.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
   */
  public function toArray($request): array
  {
    $arr = [
      'id'          => $this->id,
      'name'        => $this->name,
      'alias'       => $this->alias,
      'directory'   => $this->directory,
      'method'      => $this->method,
      'slug'        => $this->slug,
      'ordering'        => $this->ordering,
      'access'      => true,
      'icon'        => $this->icon,
      'childs'      => $this->relationValidation('recursiveMenus',function(){
        return $this->recursiveMenus->transform(function($menu){
          return new static($menu);
        });
      },$this->relationValidation('recursiveModules',function(){
        return $this->recursiveModules->transform(function($module){
          return new static($module);
        });
      }))      
    ];

    return $arr;
  }
}
