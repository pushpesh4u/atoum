<?php

namespace mageekguy\atoum\asserters;

use
	mageekguy\atoum\asserters,
	mageekguy\atoum\exceptions,
	mageekguy\atoum\tools\diffs
;

class phpArray extends asserters\variable implements \arrayAccess
{
	private $key = null;
	private $innerValue = null;
	private $innerValueIsSet = false;
	private $innerAsserter = null;

	public function __get($asserter)
	{
		switch ($asserter)
		{
			case 'keys':
				return $this->getKeysAsserter();

			case 'size':
				return $this->getSizeAsserter();

			default:
				$asserter = parent::__get($asserter);

				if ($asserter->handleNativeType() === false)
				{
					$this->resetInnerAsserter();

					return $asserter;
				}
				else
				{
					$this->innerAsserter = $asserter;
					$this->innerValue = $this->value;

					return $this;
				}
		}
	}

	public function __call($method, $arguments)
	{
		$innerAsserter = $this->setInnerAsserter($method);

		if ($innerAsserter === null)
		{
			return parent::__call($method, $arguments);
		}
		else
		{
			call_user_func_array(array($innerAsserter, $method), $arguments);

			return $this;
		}
	}

	public function getKey()
	{
		return $this->key;
	}

	public function getInnerAsserter()
	{
		return $this->innerAsserter;
	}

	public function getInnerValue()
	{
		return $this->innerValue;
	}

	public function reset()
	{
		$this->key = null;

		return parent::reset()->resetInnerAsserter();
	}

	public function offsetGet($key)
	{
		if ($this->innerAsserter === null)
		{
			return $this->hasKey($key)->value[$key];
		}
		else
		{
			if (array_key_exists($key, $this->innerValue) === false)
			{
				$this->fail(sprintf($this->getLocale()->_('%s has no key %s'), $this->getTypeOf($this->innerValue), $this->getTypeOf($key)));
			}
			else
			{
				$this->innerValue = $this->innerValue[$key];
				$this->innerValueIsSet = true;
			}

			return $this;
		}
	}

	public function offsetSet($key, $value)
	{
		throw new exceptions\logic('Tested array is read only');
	}

	public function offsetUnset($key)
	{
		throw new exceptions\logic('Array is read only');
	}

	public function offsetExists($key)
	{
		$value = ($this->innerAsserter === null ? $this->value : $this->innerValue);

		return ($value !== null && array_key_exists($key, $value) === true);
	}

	public function setWith($value)
	{
		$innerAsserter = $this->setInnerAsserter();

		if ($innerAsserter !== null)
		{
			$this->reset();

			return $innerAsserter->setWith($value);
		}
		else
		{
			parent::setWith($value);

			if (self::isArray($this->value) === true)
			{
				$this->pass();
			}
			else
			{
				$this->fail(sprintf($this->getLocale()->_('%s is not an array'), $this));
			}

			return $this;
		}
	}

	public function setByReferenceWith(& $value)
	{
		$innerAsserter = $this->setInnerAsserter();

		if ($innerAsserter !== null)
		{
			return $innerAsserter->setByReferenceWith($value);
		}
		else
		{
			parent::setByReferenceWith($value);

			if (self::isArray($this->value) === true)
			{
				$this->pass();
			}
			else
			{
				$this->fail(sprintf($this->getLocale()->_('%s is not an array'), $this));
			}

			return $this;
		}
	}

	public function hasSize($size, $failMessage = null)
	{
		if (sizeof($this->valueIsSet()->value) == $size)
		{
			$this->pass();
		}
		else
		{
			$this->fail($failMessage ?: sprintf($this->getLocale()->_('%s has not size %d'), $this, $size));
		}

		return $this;
	}

	public function isEmpty($failMessage = null)
	{
		if (sizeof($this->valueIsSet()->value) == 0)
		{
			$this->pass();
		}
		else
		{
			$this->fail($failMessage ?: sprintf($this->getLocale()->_('%s is not empty'), $this));
		}

		return $this;
	}

	public function isNotEmpty($failMessage = null)
	{
		if (sizeof($this->valueIsSet()->value) > 0)
		{
			$this->pass();
		}
		else
		{
			$this->fail($failMessage ?: sprintf($this->getLocale()->_('%s is empty'), $this));
		}

		return $this;
	}

	public function strictlyContains($value, $failMessage = null)
	{
		return $this->containsValue($value, $failMessage, true);
	}

	public function contains($value, $failMessage = null)
	{
		return $this->containsValue($value, $failMessage, false);
	}

	public function strictlyNotContains($value, $failMessage = null)
	{
		return $this->notContainsValue($value, $failMessage, true);
	}

	public function notContains($value, $failMessage = null)
	{
		return $this->notContainsValue($value, $failMessage, false);
	}

	public function atKey($key, $failMessage = null)
	{
		$this->hasKey($key, $failMessage)->key = $key;

		return $this;
	}

	public function hasKeys(array $keys, $failMessage = null)
	{
		if (sizeof($undefinedKeys = array_diff($keys, array_keys($this->valueIsSet()->value))) <= 0)
		{
			$this->pass();
		}
		else
		{
			$this->fail($failMessage ?: sprintf($this->getLocale()->_('%s should have keys %s'), $this, $this->getTypeOf($undefinedKeys)));
		}

		return $this;
	}

	public function notHasKeys(array $keys, $failMessage = null)
	{
		if (sizeof($definedKeys = array_intersect(array_keys($this->value), $keys)) <= 0)
		{
			$this->pass();
		}
		else
		{
			$this->fail($failMessage ?: sprintf($this->getLocale()->_('%s should not have keys %s'), $this, $this->getTypeOf($definedKeys)));
		}

		return $this;
	}

	public function hasKey($key, $failMessage = null)
	{
		if (array_key_exists($key, $this->valueIsSet()->value))
		{
			$this->pass();
		}
		else
		{
			$this->fail($failMessage ?: sprintf($this->getLocale()->_('%s has no key %s'), $this, $this->getTypeOf($key)));
		}

		return $this;
	}

	public function notHasKey($key, $failMessage = null)
	{
		if (array_key_exists($key, $this->value) === false)
		{
			$this->pass();
		}
		else
		{
			$this->fail($failMessage ?: sprintf($this->getLocale()->_('%s has a key %s'), $this, $this->getTypeOf($key)));
		}

		return $this;
	}

	public function containsValues(array $values, $failMessage = null)
	{
		return $this->intersect($values, $failMessage, false);
	}

	public function strictlyContainsValues(array $values, $failMessage = null)
	{
		return $this->intersect($values, $failMessage, true);
	}

	public function notContainsValues(array $values, $failMessage = null)
	{
		return $this->notIntersect($values, $failMessage, false);
	}

	public function strictlyNotContainsValues(array $values, $failMessage = null)
	{
		return $this->notIntersect($values, $failMessage, true);
	}

	public function isEqualTo($value, $failMessage = null)
	{
		return $this->callAssertion(__FUNCTION__, array($value, $failMessage));
	}

	public function isNotEqualTo($value, $failMessage = null)
	{
		return $this->callAssertion(__FUNCTION__, array($value, $failMessage));
	}

	public function isIdenticalTo($value, $failMessage = null)
	{
		return $this->callAssertion(__FUNCTION__, array($value, $failMessage));
	}

	public function isNotIdenticalTo($value, $failMessage = null)
	{
		return $this->callAssertion(__FUNCTION__, array($value, $failMessage));
	}

	public function isReferenceTo(& $reference, $failMessage = null)
	{
		return $this->callAssertion(__FUNCTION__, array(& $reference, $failMessage));
	}

	protected function containsValue($value, $failMessage, $strict)
	{
		if (in_array($value, $this->valueIsSet()->value, $strict) === true)
		{
			if ($this->key === null)
			{
				$this->pass();
			}
			else
			{
				$pass = false;

				if ($strict === false)
				{
					$pass = ($this->value[$this->key] == $value);
				}
				else
				{
					$pass = ($this->value[$this->key] === $value);
				}

				if ($pass === false)
				{
					$key = $this->key;
				}

				$this->key = null;

				if ($pass === true)
				{
					$this->pass();
				}
				else
				{
					if ($strict === false)
					{
						$failMessage = sprintf($this->getLocale()->_('%s does not contain %s at key %s'), $this, $this->getTypeOf($value), $this->getTypeOf($key));
					}
					else
					{
						$failMessage = sprintf($this->getLocale()->_('%s does not strictly contain %s at key %s'), $this, $this->getTypeOf($value), $this->getTypeOf($key));
					}

					$this->fail($failMessage);
				}
			}
		}
		else
		{
			if ($failMessage === null)
			{
				if ($strict === false)
				{
					$failMessage = sprintf($this->getLocale()->_('%s does not contain %s'), $this, $this->getTypeOf($value));
				}
				else
				{
					$failMessage = sprintf($this->getLocale()->_('%s does not strictly contain %s'), $this, $this->getTypeOf($value));
				}
			}

			$this->fail($failMessage);
		}

		return $this;
	}

	protected function notContainsValue($value, $failMessage, $strict)
	{
		if (in_array($value, $this->valueIsSet()->value, $strict) === false)
		{
			$this->pass();
		}
		else
		{
			if ($this->key === null)
			{
				if ($failMessage === null)
				{
					if ($strict === false)
					{
						$failMessage = sprintf($this->getLocale()->_('%s contains %s'), $this, $this->getTypeOf($value));
					}
					else
					{
						$failMessage = sprintf($this->getLocale()->_('%s strictly contains %s'), $this, $this->getTypeOf($value));
					}
				}

				$this->fail($failMessage);
			}
			else
			{
				$pass = false;

				if ($strict === false)
				{
					$pass = ($this->value[$this->key] != $value);
				}
				else
				{
					$pass = ($this->value[$this->key] !== $value);
				}

				if ($pass === false)
				{
					$key = $this->key;
				}

				$this->key = null;

				if ($pass === true)
				{
					$this->pass();
				}
				else
				{
					if ($strict === false)
					{
						$failMessage = sprintf($this->getLocale()->_('%s contains %s at key %s'), $this, $this->getTypeOf($value), $this->getTypeOf($key));
					}
					else
					{
						$failMessage = sprintf($this->getLocale()->_('%s strictly contains %s at key %s'), $this, $this->getTypeOf($value), $this->getTypeOf($key));
					}

					$this->fail($failMessage);
				}
			}
		}

		return $this;
	}

	protected function intersect(array $values, $failMessage, $strict)
	{
		$unknownValues = array();

		foreach ($values as $value) if (in_array($value, $this->value, $strict) === false)
		{
			$unknownValues[] = $value;
		}

		if (sizeof($unknownValues) <= 0)
		{
			$this->pass();
		}
		else
		{
			if ($failMessage === null)
			{
				if ($strict === false)
				{
					$failMessage = sprintf($this->getLocale()->_('%s does not contain values %s'), $this, $this->getTypeOf($unknownValues));
				}
				else
				{
					$failMessage = sprintf($this->getLocale()->_('%s does not contain strictly values %s'), $this, $this->getTypeOf($unknownValues));
				}
			}

			$this->fail($failMessage);
		}

		return $this;
	}

	protected function notIntersect(array $values, $failMessage, $strict)
	{
		$knownValues = array();

		foreach ($values as $value) if (in_array($value, $this->value, $strict) === true)
		{
			$knownValues[] = $value;
		}

		if (sizeof($knownValues) <= 0)
		{
			$this->pass();
		}
		else
		{
			if ($failMessage === null)
			{
				if ($strict === false)
				{
					$failMessage = sprintf($this->getLocale()->_('%s should not contain values %s'), $this, $this->getTypeOf($knownValues));
				}
				else
				{
					$failMessage = sprintf($this->getLocale()->_('%s should not contain strictly values %s'), $this, $this->getTypeOf($knownValues));
				}
			}

			$this->fail($failMessage);
		}

		return $this;
	}

	protected function valueIsSet($message = 'Array is undefined')
	{
		return parent::valueIsSet($message);
	}

	protected function getKeysAsserter()
	{
		return $this->generator->__call('phpArray', array(array_keys($this->valueIsSet()->value)));
	}

	protected function getSizeAsserter()
	{
		return $this->generator->__call('integer', array(sizeof($this->valueIsSet()->value)));
	}

	protected function setInnerAsserter($method = null)
	{
		$asserter = null;

		if ($this->innerAsserter !== null)
		{
			if ($method === null)
			{
				$asserter = $this->innerAsserter;
			}
			else if ($this->innerValueIsSet === true && method_exists($this->innerAsserter, $method) === true)
			{
				$asserter = $this->innerAsserter->setWith($this->innerValue);
			}
		}

		return $asserter;
	}

	protected function callAssertion($method, array $arguments)
	{
		call_user_func_array(array($this->setInnerAsserter($method) ?: 'parent', $method), $arguments);

		return $this;
	}

	protected static function check($value, $method)
	{
		if (self::isArray($value) === false)
		{
			throw new exceptions\logic\invalidArgument('Argument of ' . $method . '() must be an array');
		}
	}

	protected static function isArray($value)
	{
		return (is_array($value) === true);
	}

	private function resetInnerAsserter()
	{
		$this->innerAsserter = null;
		$this->innerValue = null;
		$this->innerValueIsSet = false;

		return $this;
	}
}
