<?php
namespace OAuth2Provider\Service\AbstractFactory;

use OAuth2Provider\Exception;
use OAuth2Provider\Lib\Utilities;

use OAuth2\Request;

use Zend\ServiceManager;

class RequestAbstractFactory implements ServiceManager\AbstractFactoryInterface
{
    /**
     * @var string
     */
    const REGEX_REQUEST_PATTERN = '~^oauth2provider.server.([a-zA-Z0-9_]+).request$~';

    /**
     * @var string
     */
    protected $serverKey;

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceManager\ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        // for performance, do a prelim check before checking against regex
        if (0 !== strpos($requestedName, 'oauth2provider.server.')) {
            return false;
        }

        if (preg_match(static::REGEX_REQUEST_PATTERN, $requestedName, $matches)
            && !empty($matches[1])
        ) {
            $this->serverKey = ($matches[1] === 'main')
                ? $serviceLocator->get('OAuth2Provider/Options/Configuration')->getMainServer()
                : $matches[1];

            if (Utilities::hasSMInstance($serviceLocator, "oauth2provider.server.{$this->serverKey}")) {
                return true;
            } else {
                throw new Exception\ErrorException(sprintf(
                    "Error '%s': server '%s' is not initialized yet",
                    __METHOD__,
                    "oauth2provider.server.{$this->serverKey}"
                ));
            }
        }

        return false;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceManager\ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $requestContainer = $serviceLocator->get('OAuth2Provider/Containers/RequestContainer');
        $requestContainer[$this->serverKey] = Request::createFromGlobals();

        return $requestContainer->getServerContents($this->serverKey);
    }
}
