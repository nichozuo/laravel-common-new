<?php

namespace LaravelCommonNew\App\Helpers;

use App\Models\SysPermissions;
use Illuminate\Support\Arr;

class LaravelPermissionHelper
{
    private array $list = [];
    private array $ids = [];
    private int $listCount = 0;

    /**
     * @param $user
     * @return array
     */
    public function getPermissionByUser($user): array
    {
        // 获取所有权限
        $permissions = $user->getAllPermissions();
        if ($permissions->count() == 0)
            return [];
        // 补充父级
        $permissions->each(function ($item) {
            $this->getParent($item);
        });
        // 排序
        $this->list = array_values(Arr::sort($this->list, function ($value) {
            return $value['_lft'];
        }));
        // 生成树形结构
        return $this->toTree();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getPermissionByNodeId(int $id): mixed
    {
        return SysPermissions::descendantsAndSelf($id)->toTree()->first();
    }

    /**
     * @param $item
     * @return void
     */
    private function getParent($item): void
    {
        if (!in_array($item->id, $this->ids)) {
            $this->ids[] = $item->id;
            $this->list[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'title' => $item['title'],
                'type' => $item['type'],
                'url' => $item['url'],
                //'permission' => $item['permission'],
                'icon' => $item['icon'],
                '_lft' => $item['_lft'],
                'parent_id' => $item['parent_id'],
            ];
        }
        if ($item->parent_id != null) {
            $parent = SysPermissions::findOrFail($item->parent_id);
            $this->getParent($parent);
        }
    }

    /**
     * @return mixed
     */
    private function toTree(): mixed
    {
        $this->listCount = count($this->list) - 1;
        $this->list[0]['children'] = $this->getChildren(1, $this->list[0]['id']);
        return $this->list[0];
    }

    /**
     * @param $start
     * @param $parent_id
     * @return array
     */
    private function getChildren($start, $parent_id): array
    {
        $node = [];
        if ($start > $this->listCount)
            return $node;

        foreach (range($start, $this->listCount) as $index) {
            if ($this->list[$index]['parent_id'] == $parent_id) {
                $t1 = $this->list[$index];
                $t1['children'] = $this->getChildren($index + 1, $t1['id']);
                $node[] = $t1;
            }
        }
        return $node;
    }
}
