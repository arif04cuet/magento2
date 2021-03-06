<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Core
 * @copyright   Copyright (c) 2013 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Mage_Core_Model_Theme_Validator
{
    /**
     * Helper
     *
     * @var Mage_Core_Helper_Data
     */
    protected $_helper;

    /**
     * Validators list by data key
     *
     * array('dataKey' => array('validator_name' => [validators], ...), ...)
     *
     * @var array
     */
    protected $_dataValidators = array();

    /**
     * List of errors after validation process
     *
     * array('dataKey' => 'Error message')
     *
     * @var array
     */
    protected $_errorMessages;

    /**
     * Initialize validators
     */
    public function __construct(Mage_Core_Helper_Data $helper)
    {
        $this->_helper = $helper;
        $this->_setVersionValidators();
        $this->_setTypeValidators();
        $this->_setTitleValidators();
    }

    /**
     * Set version validators
     *
     * @return Mage_Core_Model_Theme_Validator
     */
    protected function _setVersionValidators()
    {
        $versionValidators = array(
            array('name' => 'not_empty', 'class' => 'Zend_Validate_NotEmpty', 'break' => true, 'options' => array(),
                  'message' => $this->_helper->__('Field can\'t be empty')),
            array('name' => 'available', 'class' => 'Zend_Validate_Regex', 'break' => true,
                  'options' => array('pattern' => '/^(\d+\.\d+\.\d+\.\d+(\-[a-zA-Z0-9]+)?)$|^\*$/'),
                  'message' => $this->_helper->__('Theme version has not compatible format'))
        );

        $this->addDataValidators('theme_version', $versionValidators)
            ->addDataValidators('magento_version_to', $versionValidators)
            ->addDataValidators('magento_version_from', $versionValidators);

        return $this;
    }

    /**
     * Set title validators
     *
     * @return $this
     */
    protected function _setTitleValidators()
    {
        $titleValidators = array(
            array(
                'name' => 'not_empty',
                'class' => 'Zend_Validate_NotEmpty',
                'break' => true,
                'options' => array(),
                'message' => $this->_helper->__('Field title can\'t be empty')
            )
        );

        $this->addDataValidators('theme_title', $titleValidators);
        return $this;
    }

    /**
     * Set theme type validators
     *
     * @return Mage_Core_Model_Theme_Validator
     */
    protected function _setTypeValidators()
    {
        $typeValidators = array(
            array(
                'name' => 'not_empty',
                'class' => 'Zend_Validate_NotEmpty',
                'break' => true,
                'options' => array(),
                'message' => $this->_helper->__('Field can\'t be empty')
            ),
            array(
                'name' => 'available',
                'class' => 'Zend_Validate_InArray',
                'break' => true,
                'options' => array('haystack' => Mage_Core_Model_Theme::$types),
                'message' => $this->_helper->__('Theme type is invalid')
            )
        );

        $this->addDataValidators('type', $typeValidators);

        return $this;
    }

    /**
     * Add validators
     *
     * @param string $dataKey
     * @param array $validators
     * @return Mage_Core_Model_Theme_Validator
     */
    public function addDataValidators($dataKey, $validators)
    {
        if (!isset($this->_dataValidators[$dataKey])) {
            $this->_dataValidators[$dataKey] = array();
        }
        foreach ($validators as $validator) {
            $this->_dataValidators[$dataKey][$validator['name']] = $validator;
        }
        return $this;
    }

    /**
     * Return error messages for items
     *
     * @param string|null $dataKey
     * @return array
     */
    public function getErrorMessages($dataKey = null)
    {
        if ($dataKey) {
            return isset($this->_errorMessages[$dataKey]) ? $this->_errorMessages[$dataKey] : array();
        }
        return $this->_errorMessages;
    }

    /**
     * Instantiate class validator
     *
     * @param array $validators
     * @return Mage_Core_Model_Theme_Validator
     */
    protected function _instantiateValidators(array &$validators)
    {
        foreach ($validators as &$validator) {
            if (is_string($validator['class'])) {
                $validator['class'] = new $validator['class']($validator['options']);
                $validator['class']->setDisableTranslator(true);
            }
        }
        return $this;
    }

    /**
     * Validate one data item
     *
     * @param array $validator
     * @param string $dataKey
     * @param mixed $dataValue
     * @return bool
     */
    protected function _validateDataItem($validator, $dataKey, $dataValue)
    {
        if ($validator['class'] instanceof Zend_Validate_NotEmpty && !$validator['class']->isValid($dataValue)
            || !empty($dataValue) && !$validator['class']->isValid($dataValue)
        ) {
            $this->_errorMessages[$dataKey][] = $validator['message'];
            if ($validator['break']) {
                return false;
            }
        }
        return  true;
    }

    /**
     * Validate all data items
     *
     * @param Varien_Object $data
     * @return bool
     */
    public function validate(Varien_Object $data)
    {
        $this->_errorMessages = array();
        foreach ($this->_dataValidators as $dataKey => $validators) {
            if (!isset($data[$dataKey]) || !$data->dataHasChangedFor($dataKey)) {
                continue;
            }

            $this->_instantiateValidators($validators);
            foreach ($validators as $validator) {
                if (!$this->_validateDataItem($validator, $dataKey, $data[$dataKey])) {
                    break;
                }
            }
        }
        return empty($this->_errorMessages);
    }
}
