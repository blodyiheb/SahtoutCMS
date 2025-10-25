<?php
return [
    // 🔹 Page Info
    'admin_news_page_title' => 'Gestión de Noticias',
    'admin_news_meta_description' => 'Gestión de Noticias para el Servidor WoW Sahtout',
    'admin_news_title' => 'Gestión de Noticias',

    // 🔹 Status & Generic
    'admin_news_unknown_user' => 'Desconocido',
    'admin_news_no_news' => 'No hay noticias disponibles.',
    'admin_news_yes' => 'Sí',
    'admin_news_no' => 'No',

    // 🔹 CSRF / Validation
    'admin_news_csrf_error' => 'La validación del token CSRF falló.',

    // 🔹 Upload Errors
    'admin_news_upload_err_ini_size' => 'El tamaño del archivo excede el límite del servidor (upload_max_filesize).',
    'admin_news_upload_err_form_size' => 'El tamaño del archivo excede el límite del formulario.',
    'admin_news_upload_err_partial' => 'El archivo solo se subió parcialmente.',
    'admin_news_upload_err_no_file' => 'No se subió ningún archivo.',
    'admin_news_upload_err_no_tmp_dir' => 'Falta el directorio temporal.',
    'admin_news_upload_err_cant_write' => 'No se pudo escribir el archivo en el disco.',
    'admin_news_upload_err_extension' => 'Una extensión de PHP detuvo la subida.',
    'admin_news_upload_err_unknown' => 'Error de subida desconocido.',
    'admin_news_invalid_file_type' => 'Tipo de archivo inválido. Solo se permiten JPG, PNG, GIF.',
    'admin_news_file_size_exceeded' => 'El tamaño del archivo excede el límite de 2MB.',
    'admin_news_upload_dir_not_writable' => 'El directorio de subida no tiene permisos de escritura.',
    'admin_news_upload_failed' => 'No se pudo mover el archivo subido.',
    'admin_news_js_invalid_file_type' => 'Tipo de archivo inválido. Solo se permiten JPG, PNG o GIF.',
    'admin_news_js_file_size_exceeded' => 'El tamaño del archivo excede el límite de 2MB.',

    // 🔹 Database Success / Fail
    'admin_news_add_success' => 'Noticia añadida con éxito.',
    'admin_news_add_failed' => 'Error al añadir la noticia: %s',
    'admin_news_update_success' => 'Noticia actualizada con éxito.',
    'admin_news_update_failed' => 'Error al actualizar la noticia: %s',
    'admin_news_delete_success' => 'Noticia eliminada con éxito.',
    'admin_news_delete_failed' => 'Error al eliminar la noticia: %s',

    // 🔹 Form Labels & Placeholders
    'admin_news_label_title' => 'Título',
    'admin_news_placeholder_title' => 'Ingrese el título de la noticia',
    'admin_news_label_slug' => 'Slug',
    'admin_news_placeholder_slug' => 'Ingrese el slug (opcional)',
    'admin_news_label_content' => 'Contenido',
    'admin_news_placeholder_content' => 'Ingrese el contenido de la noticia',
    'admin_news_label_category' => 'Categoría',

    // 🔹 Categories
    'admin_news_category_update' => 'Actualización',
    'admin_news_category_event' => 'Evento',
    'admin_news_category_maintenance' => 'Mantenimiento',
    'admin_news_category_other' => 'Otro',

    // 🔹 Image Upload
    'admin_news_label_image' => 'Subida de Imagen (JPG, PNG, GIF, máx. 2MB, Opcional)',
    'admin_news_image_help' => 'Dejar en blanco para usar la imagen predeterminada (news.png).',
    'admin_news_image_edit_help' => 'Dejar en blanco para mantener la imagen existente (predeterminado: news.png).',
    'admin_news_image_preview_alt' => 'Vista previa de la imagen',
    'admin_news_image_alt' => 'Imagen de la Noticia',

    // 🔹 Importance
    'admin_news_label_is_important' => 'Marcar como Importante',

    // 🔹 Buttons
    'admin_news_add_button' => 'Añadir Noticia',
    'admin_news_edit_button' => 'Editar',
    'admin_news_delete_button' => 'Eliminar',
    'admin_news_close_button' => 'Cerrar',
    'admin_news_cancel_button' => 'Cancelar',
    'admin_news_save_button' => 'Guardar',

    // 🔹 Headers
    'admin_news_add_header' => 'Añadir Nueva Noticia',
    'admin_news_list_header' => 'Artículos de Noticias',

    // 🔹 Table
    'admin_news_table_title' => 'Título',
    'admin_news_table_category' => 'Categoría',
    'admin_news_table_posted_by' => 'Publicado por',
    'admin_news_table_date' => 'Fecha',
    'admin_news_table_important' => 'Importante',
    'admin_news_table_image' => 'Imagen',
    'admin_news_table_actions' => 'Acciones',

    // 🔹 Modals
    'admin_news_edit_modal_title' => 'Editar Noticia: ',
    'admin_news_delete_modal_title' => 'Eliminar Noticia: ',
    'admin_news_delete_confirm' => '¿Está seguro de que desea eliminar este artículo de noticias?',

    // 🔹 Pagination
    'admin_news_pagination_aria' => 'Paginación de Noticias',
    'admin_news_previous' => 'Anterior',
    'admin_news_next' => 'Siguiente',
];
?>
