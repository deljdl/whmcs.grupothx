<?php

namespace BitPayKeyUtils\Math;

/**
 * @package Bitcore
 */
interface EngineInterface
{
    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function add($a, $b);

    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function cmp($a, $b);

    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function div($a, $b);

    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function invertm($a, $b);

    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function mod($a, $b);

    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function mul($a, $b);

    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function pow($a, $b);

    /**
     * @param string $a Numeric String
     * @param string $b Numeric String
     */
    public function sub($a, $b);
}
