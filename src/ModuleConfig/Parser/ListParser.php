<?php
/**
 * Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Qobo Ltd. (https://www.qobo.biz)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Qobo\Utils\ModuleConfig\Parser;

use Cake\Utility\Hash;
use JsonSchema\Constraints\Constraint;
use Qobo\Utils\Utility\Convert;

/**
 * List JSON Parser
 *
 * This parser is useful for lists config JSON processing.
 *
 * @author Leonid Mamchenkov <l.mamchenkov@qobo.biz>
 */
class ListParser extends Parser
{
    /**
     * Default options
     * @var array
     */
    protected $_defaultConfig = [
        'allowEmptyData' => true,
        'allowEmptySchema' => true,
        'lint' => false,
        'validate' => true,
        'validationMode' => Constraint::CHECK_MODE_APPLY_DEFAULTS,
        'filter' => false,
        'flatten' => false,
    ];

    /**
     * {@inheritDoc}
     */
    public function parse(string $path, array $options = []): \stdClass
    {
        $result = parent::parse($path, $options);
        $data = Convert::objectToArray($result);

        $config = $this->getConfig();

        if (isset($config['transition']) && $config['transition']) {
            $data['items'] = $this->transition($data['items'], $config['transition']);
        }

        $data['items'] = $this->normalize($data['items']);

        if ($config['filter']) {
            $data['items'] = $this->filter($data['items']);
        }

        if ($config['flatten']) {
            $data['items'] = $this->flatten($data['items']);
        }

        $result = Convert::arrayToObject($data);

        return $result;
    }

    /**
     * Method that restructures list options csv data for better handling.
     *
     * @param mixed[] $data csv data
     * @param string|null $prefix nested option prefix
     * @return mixed[]
     */
    protected function normalize(array $data, string $prefix = null): array
    {
        if ($prefix) {
            $prefix .= '.';
        }

        $result = [];
        foreach ($data as $item) {
            $value = [
                'label' => (string)$item['label'],
                'inactive' => (bool)$item['inactive']
            ];

            if (! empty($item['children'])) {
                $value['children'] = $this->normalize($item['children'], $prefix . $item['value']);
            }
            $result[$prefix . $item['value']] = $value;
        }

        return $result;
    }

    /**
     * Method that filters list options, excluding non-active ones
     *
     * @param  mixed[]  $data list options
     * @return mixed[]
     */
    protected function filter(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if ($value['inactive']) {
                continue;
            }

            $result[$key] = $value;
            if (isset($value['children'])) {
                $result[$key]['children'] = $this->filter($value['children']);
            }
        }

        return $result;
    }

    /**
     * Flatten list options.
     *
     * @param mixed[] $data List options
     * @return mixed[]
     */
    protected function flatten(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $item = [
                'label' => $value['label'],
                'inactive' => $value['inactive']
            ];

            $result[$key] = $item;

            if (isset($value['children'])) {
                $result = array_merge($result, $this->flatten($value['children']));
            }
        }

        return $result;
    }

    /**
     * Filter the list with the possible values from a parent key
     *
     * @param  mixed[] $data  List Array
     * @param  string $value Current list value
     * @return mixed[] filter array
     */
    protected function transition(array $data, string $value) : array
    {
        $list = (Hash::extract($data, '{n}[value=' . $value . '].transitions.{*}'));

        if (empty($list)) {
            return $data;
        }

        $list[] = $value;

        return $result = array_filter($data, function ($v) use ($list) {
            return in_array($v['value'], (array)$list);
        });
    }
}
