<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Annotations;

/**
 * Simple lexer for docblock annotations.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Lexer extends \Doctrine\Common\Lexer
{
    const T_NONE = 1;
    const T_FLOAT = 2;
    const T_INTEGER = 3;
    const T_STRING = 4;
    const T_IDENTIFIER = 5;
    const T_TRUE = 6;
    const T_FALSE = 7;
    
    /**
     * @inheritdoc
     */
    protected function getCatchablePatterns()
    {
        return array(
            '[a-z_][a-z0-9_\\\]*',
            '(?:[0-9]+(?:[\.][0-9]+)*)(?:e[+-]?[0-9]+)?',
            '"(?:[^"]|"")*"'
        );
    }
    
    /**
     * @inheritdoc
     */
    protected function getNonCatchablePatterns()
    {
        return array('\s+', '\*+', '(.)');
    }

    /**
     * @inheritdoc
     */
    protected function _getType(&$value)
    {
        $type = self::T_NONE;

        $newVal = $this->_getNumeric($value);
        if ($newVal !== false){
            $value = $newVal;
            if (strpos($value, '.') !== false || stripos($value, 'e') !== false) {
                $type = self::T_FLOAT;
            } else {
                $type = self::T_INTEGER;
            }
        }
        if ($value[0] === '"') {
            $type = self::T_STRING;
            $value = str_replace('""', '"', substr($value, 1, strlen($value) - 2));
        } else if (ctype_alpha($value[0]) || $value[0] === '_') {
            $type = $this->_checkLiteral($value);
        }

        return $type;
    }

    /**
     * @todo Doc
     */
    private function _getNumeric($value)
    {
        if ( ! is_scalar($value)) {
            return false;
        }
        // Checking for valid numeric numbers: 1.234, -1.234e-2
        if (is_numeric($value)) {
            return $value;
        }

        return false;
    }
    
    /**
     * Checks if an identifier is a keyword and returns its correct type.
     *
     * @param string $identifier identifier name
     * @return int token type
     */
    private function _checkLiteral($identifier)
    {
        $name = 'Doctrine\Common\Annotations\Lexer::T_' . strtoupper($identifier);

        if (defined($name)) {
            return constant($name);
        }

        return self::T_IDENTIFIER;
    }
}