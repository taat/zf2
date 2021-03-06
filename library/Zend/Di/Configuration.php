<?php

namespace Zend\Di;

use Traversable;

class Configuration
{
    protected $data = array();
    
    /**
     * @var Zend\Di\DependencyInjector
     */
    protected $di = null;
    
    public function __construct($data)
    {
        if ($data instanceof Traversable) {
            if (method_exists($data, 'toArray')) {
                $data = $data->toArray();
            } else {
                $data = iterator_to_array($data, true);
            }
        } elseif (!is_array($data)) {
            throw new Exception\InvalidArgumentException('Configuration data must be of type Zend\Config\Config or an array');
        }
        $this->data = $data;
    }
    
    public function configure(DependencyInjector $di)
    {
        if (isset($this->data['definition'])) {
            $this->configureDefinition($di, $this->data['definition']);
        }
        
        if (isset($this->data['definitions'])) {
            $this->configureDefinitions($di, $this->data['definitions']);
        }
        
        /*
        if (isset($this->data['compiler'])) {
            $this->configureCompiler($di, $this->data['compiler']);
        }
        */
        
        if (isset($this->data['instance'])) {
            $this->configureInstance($di, $this->data['instance']);
        }
        
    }
    
    public function configureDefinitions(DependencyInjector $di, $definitionsData)
    {
        if ($di->hasDefinition()) {
            if (!$di->getDefinition() instanceof Definition\AggregateDefinition) {
                throw new Exception\InvalidArgumentException('In order to configure multiple definitions, the primary definition must not be set, or must be of type AggregateDefintion');
            }
        } else {
            $di->setDefinition($di->createDefinition('Zend\Di\Definition\AggregateDefinition'));
        }

        foreach ($definitionsData as $definitionData) {
            $this->configureDefinition($di, $definitionData);
        }
    }
    
    public function configureDefinition(DependencyInjector $di, $definitionData)
    {
        if ($di->hasDefinition()) {
            $aggregateDef = $di->getDefinition();
            if (!$aggregateDef instanceof Definition\AggregateDefinition) {
                throw new Exception\InvalidArgumentException('In order to configure multiple definitions, the primary definition must not be set, or must be of type AggregateDefintion');
            }
        } /* else {
            $aggregateDef = $di->createDefinition('Zend\Di\Definition\AggregateDefinition');
            $di->setDefinition($aggregateDef);
        } */
        
        if (isset($definitionData['class'])) {
            $definition = $di->createDefinition($definitionData['class']);
            unset($definitionData['class']);
            if ($definition instanceof Definition\BuilderDefinition) {
                $definition->createClassesFromArray($definitionData);
            } else {
                // @todo other types
            }
        }
        
        if (isset($aggregateDef)) {
            $aggregateDef->addDefinition($definition);
        } else {
            $di->setDefinition($definition);
        }
    }
    
    public function configureInstance(DependencyInjector $di, $instanceData)
    {
        $im = $di->getInstanceManager();
        
        foreach ($instanceData as $target => $data) {
            switch (strtolower($target)) {
                case 'aliases':
                case 'alias':
                    foreach ($data as $aliasName => $className) {
                        $im->addAlias($aliasName, $className);
                    }
                    break;
                case 'properties':
                case 'property':
                    foreach ($data as $classOrAlias => $properties) {
                        foreach ($properties as $propName => $propValue) {
                            $im->setProperty($classOrAlias, $propName, $propValue);
                        }
                    }
                    break;
                case 'preferences':
                case 'preferredinstances':
                case 'preferredinstance':
                    foreach ($data as $classOrAlias => $preferredValueOrValues) {
                        if (is_array($preferredValueOrValues)) {
                            foreach ($preferredValueOrValues as $preferredValue) {
                                $im->addPreferredInstance($classOrAlias, $preferredValue);
                            }
                        } else {
                            $im->addPreferredInstance($classOrAlias, $preferredValueOrValues);
                        }
                    }
            }
        }
    }
    
}
