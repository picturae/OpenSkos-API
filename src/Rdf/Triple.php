<?php declare(strict_types=1);

namespace App\Rdf;

final class Triple {
    /**
     * @var string
     */
    private $subject;
    /**
     * @var string
     */
    private $predicate;
    /**
     * @var string
     */
    private $object;

    public function __construct(string $subject, string $predicate, string $object)
    {
        $this->subject = $subject;
        $this->predicate = $predicate;
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getPredicate(): string
    {
        return $this->predicate;
    }

    /**
     * @return string
     */
    public function getObject(): string
    {
        return $this->object;
    }


}