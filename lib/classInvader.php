<?php

// This file is part of CodeRunnerEx
//
// CodeRunnerEx is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// CodeRunnerEx is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with CodeRunnerEx. If not, see <http://www.gnu.org/licenses/>.

class ClassInvader
{
    public $obj;
    public $reflected;

    public function __construct(object $currObj, object $targetReflection = null)
    {
        $this->obj = $currObj;
        $target = empty($targetReflection)? new ReflectionClass($currObj): $targetReflection;
        $this->reflected = $target;
    }

    public function __get(string $name)  // : mixed
    {
        $property = $this->reflected->getProperty($name);

        $property->setAccessible(true);

        return $property->getValue($this->obj);
    }

    public function __set(string $name, /*mixed*/ $value): void
    {
        $property = $this->reflected->getProperty($name);

        $property->setAccessible(true);

        $property->setValue($this->obj, $value);
    }

    public function __call(string $name, array $params = [])  //: mixed
    {
        $method = $this->reflected->getMethod($name);

        $method->setAccessible(true);

        return $method->invoke($this->obj, ...$params);
    }
}

function invade($obj) {
    return new ClassInvader($obj);
}
function invade_parent($obj) {
    $class = new ReflectionClass($obj);
    $parent = $class->getParentClass();
    return new ClassInvader($obj, $parent);
}
