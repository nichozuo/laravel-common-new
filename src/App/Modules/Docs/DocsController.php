<?php

namespace LaravelCommonNew\App\Modules\Docs;

use Doctrine\DBAL\Exception;
use LaravelCommonNew\App\Attributes\ActionAttribute;
use LaravelCommonNew\App\Attributes\ControllerAttribute;
use LaravelCommonNew\App\Helpers\DbalHelper;
use LaravelCommonNew\App\Helpers\DocsHelper;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

#[ControllerAttribute("文档", auth: false)]
class DocsController extends BaseController
{
    private string $basePath;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        DbalHelper::register();
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    #[ActionAttribute("菜单")]
    public function getMenu(Request $request): array
    {
        $params = $request->validate([
            'type' => 'required|string',
        ]);
        return match ($params['type']) {
            'readme' => DocsHelper::GetReadmeMenu(),
            'modules' => DocsHelper::GetModulesMenu(app_path('Modules' . DIRECTORY_SEPARATOR)),
            'database' => DocsHelper::GetDatabaseMenu(),
            default => [],
        };
    }

    /**
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    #[ActionAttribute("内容")]
    public function getContent(Request $request): array
    {
        $params = $request->validate([
            'type' => 'required|string',
            'key' => 'required|string',
        ]);
        return match ($params['type']) {
            'readme' => DocsHelper::GetReadmeContent($params['key']),
            'modules' => DocsHelper::GetModulesContent($params['key']),
            'database' => DocsHelper::GetDatabaseContent($params['key']),
            default => [],
        };
    }
}
