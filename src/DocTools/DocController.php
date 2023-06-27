<?php

namespace LaravelCommonNew\DocTools;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DocController extends Controller
{
    public function getMenu(Request $request)
    {
        $params = $request->validate([
            'type' => 'required|in:enum,dict,db,api',
        ]);
        $type = $params['type'];
        return match ($type) {
            'api' => $this->getApiMenu(),
            'enum' => $this->getEnumMenu(),
            'dict' => $this->getDictMenu(),
            'db' => $this->getDbMenu(),
            default => [],
        };
    }

    private function getApiMenu()
    {

    }
}