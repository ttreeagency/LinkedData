<?php

namespace Ttree\LinkedData\Eel\Helper;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\Package;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Eel\Utility;
use Neos\Flow\Exception;
use Neos\Flow\Annotations as Flow;

final class LinkedDataHelper implements ProtectedContextAwareInterface
{
    /**
     * @var string
     */
    protected $configurationPath = 'options.TtreeLinkedData:Generator';

    /**
     * @Flow\InjectConfiguration(path="defaultContext", package="Neos.Fusion")
     * @var array
     */
    protected $defaultContextConfiguration;

    /**
     * @Flow\Inject(lazy=false)
     * @var EelEvaluatorInterface
     */
    protected $eelEvaluator;

    public function render(NodeInterface $node, $preset = 'default'): string
    {
        return $this->wrap(
            $this->item($node, $preset)
        );
    }

    public function list(array $collection, $preset = 'default', bool $withContext = true): array
    {
        $data = array_map(function (NodeInterface $node) use ($preset, $withContext) {
            return $this->item($node, $preset, $withContext);
        }, $collection);

        $count = count($data);

        if ($count === 0) {
            return $data;
        }

        if ($count === 1) {
            return $data[0];
        }

        return $data;
    }

    public function item(NodeInterface $node, $preset = 'default', bool $withContext = true): array
    {
        $configuration = $this->getConfiguration($node, $preset);
        $fragment = $configuration['fragment'];
        if ($configuration === null) {
            throw new Exception(sprintf('Missing options.TtreeLinkedData:Generator configuration for the current node type (%s)', $node->getNodeType()), 1497984924);
        }

        $contextVariables = $this->prepareContextVariables($configuration, $node, $preset);
        array_walk_recursive($fragment, function (&$item) use ($contextVariables) {
            if ($this->isEelExpression($item)) {
                $item = $this->replaceExpression($item, $contextVariables);
            }
        });

        $this->cleanup($fragment);

        if ($withContext === false) {
            unset($fragment['@context']);
        }

        return $fragment;
    }

    protected function prepareContextVariables(array $configuration, NodeInterface $node, string $preset)
    {
        $contextVariables = [
            'node' => $node,
            'preset' => $preset
        ];
        if (isset($configuration['context'])) {
            \array_walk($configuration['context'], function ($item, $key) use (&$contextVariables) {
                if ($this->isEelExpression($item)) {
                    $item = $this->replaceExpression($item, $contextVariables);
                }
                $contextVariables[$key] = $item;
            });
        }

        return $contextVariables;
    }

    protected function cleanup(array &$array)
    {
        foreach ($array as $key => &$value) {
            if ($value === null || (\is_scalar($value) && trim($value) === '')) {
                unset($array[$key]);
            } else {
                if (is_array($value)) {
                    $this->cleanup($value);
                }
            }
        }
    }

    protected function getConfiguration(NodeInterface $node, $preset = 'default')
    {
        return $node->getNodeType()->getConfiguration($this->configurationPath . '.' . $preset);
    }

    protected function isEelExpression(string $expression)
    {
        return preg_match(Package::EelExpressionRecognizer, $expression);
    }

    protected function wrap(array $content): string
    {
        return '<script type="application/ld+json">' . \json_encode($content, \JSON_PRETTY_PRINT) . '</script>';
    }

    public function replaceExpression(string $expression, array $contextVariables)
    {
        return Utility::evaluateEelExpression($expression, $this->eelEvaluator, $contextVariables, $this->defaultContextConfiguration);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
