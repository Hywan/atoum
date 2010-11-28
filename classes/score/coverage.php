<?php

namespace mageekguy\atoum\score;

use \mageekguy\atoum;
use \mageekguy\atoum\score;
use \mageekguy\atoum\exceptions;

class coverage
{
	protected $lines = array();
	protected $methods = array();
	protected $reflectionClassInjector = null;

	public function __construct() {}

	public function getReflectionClass($class)
	{
		$reflectionClass = null;

		if ($this->reflectionClassInjector === null)
		{
			$reflectionClass = new \reflectionClass($class);
		}
		else
		{
			$reflectionClass = $this->reflectionClassInjector->__invoke($class);

			if ($reflectionClass instanceof \reflectionClass === false)
			{
				throw new exceptions\runtime\unexpectedValue('Reflection class injector must return a \reflectionClass instance');
			}
		}

		return $reflectionClass;
	}

	public function setReflectionClassInjector(\closure $reflectionClassInjector)
	{
		$closure = new \reflectionMethod($reflectionClassInjector, '__invoke');

		if ($closure->getNumberOfParameters() !== 1)
		{
			throw new exceptions\logic\invalidArgument('Reflection class injector must take one argument');
		}

		$this->reflectionClassInjector = $reflectionClassInjector;

		return $this;
	}

	public function getLines()
	{
		return $this->lines;
	}

	public function addXdebugData(atoum\test $test, array $data)
	{
		if (sizeof($data) > 0)
		{
			try
			{
				$testedClassName = $test->getTestedClassName();
				$testedClass = $this->getReflectionClass($testedClassName);
				$testedClassFile = $testedClass->getFileName();

				if (isset($this->methods[$testedClassFile]) === false)
				{
					$this->methods[$testedClassFile] = array();

					foreach ($testedClass->getMethods() as $method)
					{
						if ($method->isAbstract() === false && $method->getFileName() === $testedClassFile)
						{
							$endLine = $method->getEndLine();

							for ($line = $method->getStartLine(); $line <= $endLine; $line++)
							{
								$this->lines[$testedClassFile][$line] = 0;
								$this->methods[$testedClassFile][$method->getName()][$line] = & $this->lines[$testedClassFile][$line];
							}
						}
					}
				}

				foreach ($data as $file => $lines)
				{
					if ($file === $testedClassFile)
					{
						foreach ($lines as $line => $number)
						{
							if (isset($this->lines[$testedClassFile][$line]) === true)
							{
								$this->lines[$testedClassFile][$line] += $number;
							}
						}
					}
				}
			}
			catch (\exception $exception) {}
		}

		return $this;
	}

	public function merge(score\coverage $coverage)
	{
		foreach ($coverage->getLines() as $file => $lines)
		{
			foreach ($lines as $line => $number)
			{
				$this->lines[$file][$line] = (isset($this->lines[$file][$line]) === false ? $number : $this->lines[$file][$line] + $number);
			}
		}

		return $this;
	}
}

?>
