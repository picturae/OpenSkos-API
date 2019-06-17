<?php

declare(strict_types=1);

namespace App\Rdf\Format;

final class RdfFormatFactory
{
    /**
     * @var array<string,RdfFormat>
     */
    private $formats = [];

    /**
     * RdfFormatFactory constructor.
     *
     * @param RdfFormat[] $formats
     */
    private function __construct(
        array $formats
    ) {
        foreach ($formats as $format) {
            $this->formats[$format->name()] = $format;
        }
    }

    /**
     * @param string $name
     *
     * @return RdfFormat
     *
     * @throws UnknownFormatException
     */
    public function createFromName(string $name): RdfFormat
    {
        if (!$this->exists($name)) {
            throw UnknownFormatException::create($name);
        }

        return $this->formats[$name];
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function exists(string $name): bool
    {
        return isset($this->formats[$name]);
    }

    /**
     * @return RdfFormat[]
     */
    public function formats(): array
    {
        return $this->formats;
    }

    public static function loadDefault(): RdfFormatFactory
    {
        return new RdfFormatFactory([
            JsonLd::instance(),
            RdfXml::instance(),
            Turtle::instance(),
            Ntriples::instance(),
            Html::instance(),
        ]);
    }

    //TODO: load dynamically
    // public static function loadFromNamespace(string $namespace)
}
