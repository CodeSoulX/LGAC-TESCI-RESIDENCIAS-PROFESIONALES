<?php

function obtenerOCrearCategoriaPublicacion(PDO $db, string $nombre): int
{
    $nombre = trim($nombre);
    if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 80) {
        throw new InvalidArgumentException('La categoría debe tener entre 1 y 80 caracteres.');
    }

    $consulta = $db->prepare(
        'SELECT id FROM categorias WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(?)) LIMIT 1'
    );
    $consulta->execute([$nombre]);
    $categoriaId = $consulta->fetchColumn();
    if ($categoriaId !== false) {
        return (int) $categoriaId;
    }

    $insertar = $db->prepare("INSERT INTO categorias (nombre, icono) VALUES (?, 'folder')");
    $insertar->execute([$nombre]);
    return (int) $db->lastInsertId();
}

function resolverCategoriaPublicacion(PDO $db, string $seleccion, string $nuevaCategoria): int
{
    if ($seleccion === 'nueva') {
        return obtenerOCrearCategoriaPublicacion($db, $nuevaCategoria);
    }

    if (!ctype_digit($seleccion) || (int) $seleccion < 1) {
        throw new InvalidArgumentException('Selecciona una categoría válida.');
    }

    $consulta = $db->prepare('SELECT id FROM categorias WHERE id = ? LIMIT 1');
    $consulta->execute([(int) $seleccion]);
    $categoriaId = $consulta->fetchColumn();
    if ($categoriaId === false) {
        throw new InvalidArgumentException('La categoría seleccionada no existe.');
    }

    return (int) $categoriaId;
}
