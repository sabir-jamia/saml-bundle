<?php
namespace SAMLBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\Configuration\TemplatePhp;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Config;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LoginController extends AdminController
{
    /**
     * @Route("/admin/login", name="pimcore_admin_login")
     * @Route("/admin/login/", name="pimcore_admin_login_fallback")
     *
     * @TemplatePhp()
     */
    public function loginAction(Request $request)
    {
        if ($request->get('_route') === 'pimcore_admin_login_fallback') {
            return $this->redirectToRoute('pimcore_admin_login', $request->query->all(), Response::HTTP_MOVED_PERMANENTLY);
        }

        if (!is_file(\Pimcore\Config::locateConfigFile('system.php'))) {
            return $this->redirect('/install');
        }

        $user = $this->getAdminUser();
        if ($user instanceof UserInterface) {
            return $this->redirectToRoute('pimcore_admin_index');
        }

        $view = $this->buildLoginPageViewModel();

        if ($request->get('auth_failed')) {
            $view->error = 'error_auth_failed';
        }
        if ($request->get('session_expired')) {
            $view->error = 'error_session_expired';
        }

        return $view;
    }
    
    /**
     * @return ViewModel
     */
    protected function buildLoginPageViewModel()
    {
        $bundleManager = $this->get('pimcore.extension.bundle_manager');

        $view = new ViewModel([
            'config' => Config::getSystemConfig(),
            'pluginCssPaths' => $bundleManager->getCssPaths()
        ]);

        return $view;
    }
}