<?php
namespace SAMLBundle\Controller;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Pimcore\Controller\FrontendController;
use Pimcore\Tool\Authentication;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SAML2\Compat\{ContainerSingleton, AbstractContainer};
use SAMLBundle\Service\RequestService as SAMLRequest;
use SAMLBundle\Service\ResponseService as SAMLResponse;
use SAMLBundle\Service\UserService;

/**
 * @Route("saml/")
 */
class DefaultController extends FrontendController
{
    public function __construct(AbstractContainer $container) 
    {
        ContainerSingleton::setContainer($container);
    }

    /**
     * @Route("login", name="saml_login")
     * @Route("login/", name="saml_login_fallback")
     * 
     * @param SAMLRequestService $SAMLRequest
     */
    public function indexAction(SAMLRequest $SAMLRequest)
    {
        $SAMLRequest->make();
        $SAMLRequest->send();
    }
    
    /**
     * @Route("sso")
     * @Route("sso/")
     * 
     * @param UserService $userService
     * @param SAMLResponse $SAMLResponse
     * @return Returns a RedirectResponse to the pimcore_admin_login_check.
     */
    public function ssoAction(UserService $userService, SAMLResponse $SAMLResponse)
    {
        $SAMLResponse->recieve();
        $attributeData = $SAMLResponse->getAttributes();
        
        $username = trim($attributeData['name']);
        $user = $userService->getUserByUsername($username);
        
        if(!$user) {        
            $hash = Authentication::getPasswordHash($username, 'Welcome@123');
            $userData = [
                'firstname' => $attributeData['givenname'],
                'lastname' => $attributeData['surname'],
                'email' => $attributeData['name'],
                'parentId' => 0,
                'username' => $username,
                'password' => $hash,
                'active' => true,
                'rid' => ''
            ];
            $user = $userService->save($userData);
        }
        
        $token = Authentication::generateToken($username, $user->getPassword());
        $loginUrl = $this->generateUrl('pimcore_admin_login_check', [
                    'username' => $username,
                    'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);
        
        return $this->redirect($loginUrl);
    }
}