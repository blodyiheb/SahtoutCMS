<?php
return [
    // 🔹 Page Info
    'admin_shop_page_title' => 'Gestión de Tienda - Panel de Administración',
    'admin_shop_meta_description' => 'Gestión de la Tienda para el Servidor WoW Sahtout',
    'admin_shop_title' => 'Gestión de Tienda',

    // 🔹 Headers
    'admin_shop_add_edit_header' => 'Añadir/Editar Ítem de la Tienda',
    'admin_shop_list_header' => 'Ítems de la Tienda',

    // 🔹 General Status
    'admin_shop_operation_success' => '¡Operación exitosa!',
    'admin_shop_operation_error' => 'Ocurrió un error.',
    'admin_shop_db_error' => 'Error en la base de datos: %s',
    'admin_shop_no_items' => 'No se encontraron ítems',

    // 🔹 Validation
    'admin_shop_invalid_category' => 'Categoría inválida',
    'admin_shop_invalid_entry_id' => 'ID de entrada inválido',
    'admin_shop_invalid_level_boost' => 'El aumento de nivel debe estar entre 2 y 255',

    // 🔹 Upload Errors
    'admin_shop_upload_dir_not_writable' => 'El directorio de subida no tiene permisos de escritura',
    'admin_shop_upload_err_ini_size' => 'El tamaño del archivo excede el límite del servidor (upload_max_filesize)',
    'admin_shop_upload_err_form_size' => 'El tamaño del archivo excede el límite del formulario',
    'admin_shop_upload_err_partial' => 'El archivo solo se subió parcialmente',
    'admin_shop_upload_err_no_file' => 'No se subió ningún archivo',
    'admin_shop_upload_err_no_tmp_dir' => 'Falta el directorio temporal',
    'admin_shop_upload_err_cant_write' => 'No se pudo escribir el archivo en el disco',
    'admin_shop_upload_err_extension' => 'Una extensión de PHP detuvo la subida',
    'admin_shop_upload_err_unknown' => 'Error de subida desconocido',
    'admin_shop_invalid_file_type' => 'Tipo de archivo inválido. Solo se permiten JPG, PNG, GIF',
    'admin_shop_file_size_exceeded' => 'El tamaño del archivo excede el límite de 2MB',
    'admin_shop_tmp_file_missing' => 'El archivo temporal falta o no se puede leer',
    'admin_shop_file_move_failed' => 'El archivo se movió pero no se encontró',
    'admin_shop_upload_failed' => 'No se pudo mover el archivo subido',

    // 🔹 Form Labels
    'admin_shop_label_category' => 'Categoría',
    'admin_shop_label_name' => 'Nombre',
    'admin_shop_label_point_cost' => 'Costo en Puntos',
    'admin_shop_label_token_cost' => 'Costo en Tokens',
    'admin_shop_label_stock' => 'Stock',
    'admin_shop_label_entry' => 'ID del Ítem',
    'admin_shop_label_gold_amount' => 'Cantidad de Oro',
    'admin_shop_label_level_boost' => 'Aumento de Nivel (2-255)',
    'admin_shop_label_at_login_flags' => 'Opciones al iniciar sesión',
    'admin_shop_label_is_item' => '¿Es un Ítem?',
    'admin_shop_label_image' => 'Subida de Imagen (JPG, PNG, GIF, máx. 2MB)',
    'admin_shop_label_description' => 'Descripción',
    'admin_shop_label_category_filter' => 'Filtrar por Categoría',
    'admin_shop_label_search' => 'Buscar por Nombre',

    // 🔹 Placeholders
    'admin_shop_placeholder_name' => 'Ingrese el nombre del ítem',
    'admin_shop_placeholder_point_cost' => 'Ingrese el costo en puntos',
    'admin_shop_placeholder_token_cost' => 'Ingrese el costo en tokens',
    'admin_shop_placeholder_stock' => 'Dejar vacío para ilimitado',
    'admin_shop_placeholder_gold_amount' => 'Ingrese la cantidad de oro',
    'admin_shop_placeholder_level_boost' => 'Ingrese el aumento de nivel',
    'admin_shop_placeholder_description' => 'Ingrese la descripción',
    'admin_shop_placeholder_search' => 'Ingrese el nombre del ítem',

    // 🔹 Categories
    'admin_shop_category_mount' => 'Montura',
    'admin_shop_category_pet' => 'Mascota',
    'admin_shop_category_gold' => 'Oro',
    'admin_shop_category_service' => 'Servicio',
    'admin_shop_category_stuff' => 'Objetos',
    'admin_shop_all_categories' => 'Todas las Categorías',

    // 🔹 At Login Flags
    'admin_shop_at_login_none' => 'Ninguno',
    'admin_shop_at_login_force_name' => 'Forzar cambio de nombre del personaje',
    'admin_shop_at_login_reset_spells' => 'Reiniciar hechizos (también profesiones)',
    'admin_shop_at_login_reset_talents' => 'Reiniciar talentos',
    'admin_shop_at_login_customize' => 'Personalizar personaje',
    'admin_shop_at_login_reset_pet' => 'Reiniciar talentos de mascota',
    'admin_shop_at_login_first_login' => 'Primer inicio de sesión',
    'admin_shop_at_login_faction_change' => 'Cambio de facción',
    'admin_shop_at_login_race_change' => 'Cambio de raza',

    // 🔹 Buttons
    'admin_shop_add_button' => 'Añadir Ítem',
    'admin_shop_update_button' => 'Actualizar Ítem',
    'admin_shop_cancel_button' => 'Cancelar',
    'admin_shop_apply_button' => 'Aplicar',

    // 🔹 Table
    'admin_shop_table_id' => 'ID',
    'admin_shop_table_category' => 'Categoría',
    'admin_shop_table_name' => 'Nombre',
    'admin_shop_table_points' => 'Puntos',
    'admin_shop_table_tokens' => 'Tokens',
    'admin_shop_table_stock' => 'Stock',
    'admin_shop_table_image' => 'Imagen',
    'admin_shop_table_actions' => 'Acciones',

    // 🔹 Image
    'admin_shop_image_help' => 'Dejar en blanco para mantener la imagen existente al editar.',
    'admin_shop_image_preview_alt' => 'Vista previa de la imagen',
    'admin_shop_image_alt' => 'Imagen del Ítem',
    'admin_shop_no_image' => 'Sin Imagen',

    // 🔹 Misc
    'admin_shop_item_label' => 'Ítem:',
    'admin_shop_gold_label' => 'Oro:',
    'admin_shop_level_label' => 'Nivel:',
    'admin_shop_delete_confirm' => '¿Está seguro de que desea eliminar este ítem?',

    // 🔹 Pagination
    'admin_shop_pagination_aria' => 'Navegación de páginas',
    'admin_shop_previous' => 'Anterior',
    'admin_shop_next' => 'Siguiente',

    // 🔹 JS Messages
    'admin_shop_js_invalid_file_type' => 'Tipo de archivo inválido. Solo se permiten JPG, PNG o GIF.',
    'admin_shop_js_file_size_exceeded' => 'El tamaño del archivo excede el límite de 2MB.',
    'admin_shop_js_required_fields' => 'Por favor, complete todos los campos obligatorios.',
    'admin_shop_js_invalid_level_boost' => 'El aumento de nivel debe estar entre 2 y 255.',

    // 🔹 Yes / No
    'admin_shop_yes' => 'Sí',
    'admin_shop_no' => 'No',
];
?>
