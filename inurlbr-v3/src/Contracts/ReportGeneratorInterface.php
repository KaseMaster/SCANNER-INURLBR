<?php

declare(strict_types=1);

namespace Inurlbr\Contracts;

/**
 * Interface para generadores de reportes.
 */
interface ReportGeneratorInterface
{
    /**
     * Genera un reporte a partir de los resultados del escaneo.
     *
     * @param array $results Array de objetos Vulnerability.
     * @param array $metadata Metadata del escaneo (fecha, dork, engine, etc).
     * @return string El contenido del reporte generado.
     */
    public function generate(array $results, array $metadata = []): string;

    /**
     * Guarda el reporte en un archivo.
     *
     * @param string $content Contenido del reporte.
     * @param string $filename Nombre del archivo (sin extensión).
     * @param string|null $directory Directorio donde guardar (null usa directorio por defecto).
     * @return string Ruta completa del archivo guardado.
     */
    public function save(string $content, string $filename, ?string $directory = null): string;

    /**
     * Retorna la extensión del archivo que genera este report generator.
     *
     * @return string
     */
    public function getExtension(): string;
}
