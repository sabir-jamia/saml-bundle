<?php
namespace SAMLBundle\Service;

use SAML2\{AuthnRequest, Constants, Binding};
use SAML2\Compat\ContainerSingleton;

class RequestService
{
    private $request;
    
    private $container;
    
    private $spConfig;
    
    private $idpConfig;
    
    public function __construct(array $spConfig, array $idpConfig)
    {
        $this->request = new AuthnRequest();
        $this->spConfig = $spConfig;
        $this->idpConfig = $idpConfig;
        $this->container = ContainerSingleton::getInstance();
    }
    
    public function make()
    {
        $this->request->setNameIdPolicy($this->getPolicy());
        $this->request->setForceAuthn(false);
        $this->request->setIsPassive(false);
        $this->request->setProtocolBinding(Constants::BINDING_HTTP_POST);
        $this->request->setIssuer($this->spConfig['entityID']);
        $this->request->setAssertionConsumerServiceIndex(null);
        $this->request->setAttributeConsumingServiceIndex(null);
        $this->request->setAssertionConsumerServiceURL($this->spConfig['replyURL']);
        $this->request->setRelayState($this->spConfig['replyURL']);
        $this->request->setIDPList([]);
        $this->request->setRequesterID([]);
        $this->request->setId($this->container->generateId());
        $this->request->setDestination($this->getDestinationURL());
    }
    
    public function send()
    {
        if (!$this->request) {
            throw New \Exception('Empty request for saml');
        }
        
        $binding = Binding::getBinding(Constants::BINDING_HTTP_REDIRECT);
        $this->container->debugMessage($this->request->toSignedXML(), 'out');
        $binding->send($this->request);
    }
    
    private function getPolicy(): array
    {
        return [
            'Format' => $this->spConfig['NameIDFormat'],
            'AllowCreate' => true
        ];
    }
    
    private function getDestinationURL(): string
    {
        $services = $this->idpConfig['IDPSSODescriptor']['SingleSignOnService'];
        foreach ($services as $service) {
            if($service['Binding'] === Constants::BINDING_HTTP_POST) {
                return $service['Location'];
            }
        }
        
        throw new \Exception('No destination URL is set');
    }
}