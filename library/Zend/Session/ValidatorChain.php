<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Zend\Session;

use Zend\EventManager\EventManager;
use Zend\Session\Storage\StorageInterface as Storage;
use Zend\Session\Validator\ValidatorInterface as Validator;

/**
 * Validator chain for validating sessions
 */
class ValidatorChain extends EventManager
{
    /**
     * @var Storage
     */
    protected $storage;
    
    /**
     * 
     * @var array 
     */
    protected $validators = array();

    /**
     * Construct the validation chain
     *
     * @param Storage $storage
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }
    
    /**
     * Attach validators
     * 
     * @return ValidatorChain
     */
    public function attachValidators()
    {
        $metadata   = $this->storage->getMetadata('_VALID');
        $validators = $this->validators;

        if ($metadata) {
            $validators = array_unique(array_merge($validators, array_keys($metadata)));
        }

        foreach ($validators as $validator) {
            $data = isset($metadata[$validator]) ? $metadata[$validator] : null;
            $this->attach('session.validate', array(new $validator($data), 'isValid'));
        }

        return $this;
    }

    /**
     * Add session validator
     * 
     * @param string $validator
     * @return ValidatorChain
     */
    public function addValidator($validator)
    {
        if (!in_array($validator, $this->validators)) {
            $this->validators[] = $validator;
        }
        
        return $this;
    }

    /**
     * Attach a listener to the session validator chain
     *
     * @param  string $event
     * @param  callable $callback
     * @param  int $priority
     * @return \Zend\Stdlib\CallbackHandler
     */
    public function attach($event, $callback = null, $priority = 1)
    {
        $context = null;
        if ($callback instanceof Validator) {
            $context = $callback;
        } elseif (is_array($callback)) {
            $test = array_shift($callback);
            if ($test instanceof Validator) {
                $context = $test;
            }
            array_unshift($callback, $test);
        }
        if ($context instanceof Validator) {
            $data = $context->getData();
            $name = $context->getName();
            $this->getStorage()->setMetadata('_VALID', array($name => $data));
        }

        $listener = parent::attach($event, $callback, $priority);
        return $listener;
    }

    /**
     * Retrieve session storage object
     *
     * @return Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }
}
