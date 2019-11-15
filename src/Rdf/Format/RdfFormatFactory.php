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
     * @var array<string,RdfFormat>
     */
    private $mimes = [];

    /**
     * RdfFormatFactory constructor.
     *
     * @param RdfFormat[] $formats
     */
    private function __construct(
        array $formats
    ) {
        foreach ($formats as $format) {
            $this->formats[$format->name()]            = $format;
            $this->mimes[$format->contentTypeString()] = $format;
        }
    }

    /**
     * @throws UnknownFormatException
     */
    public function createFromName(string $name): RdfFormat
    {
        if (!$this->exists($name)) {
            throw UnknownFormatException::create($name);
        }

        return $this->formats[$name];
    }

    public function createFromMime(string $mime): ?RdfFormat
    {
        if (!isset($this->mimes[$mime])) {
            return null;
        }

        return $this->mimes[$mime];
    }

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
        ]);
    }

    //TODO: load dynamically
    // public static function loadFromNamespace(string $namespace)
}
