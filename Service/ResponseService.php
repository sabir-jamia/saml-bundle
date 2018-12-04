<?php
namespace SAMLBundle\Service;

use SAML2\Response as SAMLResponse;
use SAML2\Binding;

class ResponseService
{
    private $response;
    
    public function recieve()
    {
        $b = Binding::getCurrentBinding();
        $response = $b->receive();
        
        if(!$response instanceof SAMLResponse || !$response->isSuccess()){
            throw new \Exception('Not a valid SAML Response');
        }
        
        $this->response = $response;
    }
    
    public function getAttributes()
    {
        if(!$this->response) {
            throw new \Exception('Empty SAML response');
        }
        
        $assertion = $this->getAssertion();
        $attributes = $assertion->getAttributes();
        $attributeData = [];
        foreach ($attributes as $key => $attribute) {
            $attributeKey = substr(strrchr($key, '/'), 1);
            if(count($attribute) ==  1) {
                $attributeData[$attributeKey] = $attribute[0]; 
                continue;
            }
            
            $attributeData[$attributeKey] = $attribute; 
        }
        
        return $attributeData;
    }
    
    public function getAssertion()
    {
        $assertions = $this->response->getAssertions();
        return array_pop($assertions);
    }
}