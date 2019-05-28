<?php

declare(strict_types=1);

namespace App\Rdf;

final class RdfHeaders
{
    /*
     * Possible request string formats
     */
    const FORMAT_JSON_LD = 'json-ld';
    const FORMAT_RDF_XML = 'rdf';
    const FORMAT_NTRIPLES = 'ntriples';
    const FORMAT_TURTLE = 'ttl';
    const FORMAT_RDF_HTML = 'html';

    /*
     * Possible accept-header formats. Will be handled as request string formats internally
     */
    const ACCEPT_HEADER_JSON_LD = 'application/rdf+json';
    const ACCEPT_HEADER_RDF_XML = 'application/rdf+xml';
    const ACCEPT_HEADER_NTRIPLES = 'application/n-triples';
    const ACCEPT_HEADER_TURTLE = 'text/turtle';
    const ACCEPT_HEADER_HTML = 'text/html';

    /*
     * Possible content-type formats. Will be handled as request string formats internally
     */
    const CONTENT_TYPE_HEADER_JSON_LD = 'application/ld+json';
    const CONTENT_TYPE_HEADER_RDF_XML = 'application/rdf+xml';
    const CONTENT_TYPE_HEADER_NTRIPLES = 'application/n-triples';
    const CONTENT_TYPE_HEADER_TURTLE = 'text/turtle';
    const CONTENT_TYPE_HEADER_HTML = 'text/html';
}
