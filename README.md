# SAMLBundle

Symfony Bundle for Single Sign On with SAML in PIMCORE

  - Pimcore >= 5.4.4
   

# SAML Concept!
  - SAML stand for Security Assertion Markup Language.
  - It is used for exchanging Authorizationand and Authentication data between parties, in particular, between Service Provider and Identity Provider.
  - SAML Service Provider is a system entity that receives and accepts authentication assertions in conjunction with a SSO.
  - SAML Identity Provider is a system entity that issues authentication assertions in conjunction with a SSO.


# Installation...

  - Add the package simplesamlphp/saml2 in the existing pimcore application using the command composer require simplesamlphp/saml2.
  - Add the SAMLBundle under the src folder. 
  - Insert the Federation metadata obtained from the service provider application in the SAMLBundle config. (Federation metadata,  is a xml documnet that provides us data that describes a Relying Party or Claims Provider).
  - Add the config for the identity provider. Eg. 
  	- entityID: <i.e. spn:f0c4f90b-56d3-49b7-8021-53fd09714e46>
  	- NameIDFormat: urn:oasis:names:tc:SAML:2.0:nameid-format:transient
    - replyURL: <i.e http://dev.local.com/saml/sso>
    - baseURLPath: /

# How It Works---

 - First We had override the pimcore login page and inserted our customized login page having a sso login. (outlook)
 - When user click this link, the SAMLBundle creates a SAML 2.0 request with the provided configuration using simplesaml/saml2 library and sent it to the outlook login page.
 - If the user is already logged in, the response from the outlook is given instantly, otherwise the response is obtained by the application after a successful login in the outlook.
