<?php
$file = 'c:\\Users\\Annonymous\\Documents\\Fernando\\Aplicaciones\\PHP\\ContaFC\\empresas.php';
$content = file_get_contents($file);

$content = str_replace(
"    const { value: formValues } = await Swal.fire({\r\n        e = empresasCache.find(x => x.id == id);\r\n    }\r\n\r\n    const { value: formValues } = await Swal.fire({",
"    const { value: formValues } = await Swal.fire({",
$content
);

$content = str_replace(
"                \${!id ? '<div class=\"p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700 font-medium\">✨ Se inicializará automáticamente con el PUC de Honduras.</div>' : ''}\r\n                \${!id ? '<div class=\"p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700 font-medium\">✨ Se inicializará automáticamente con el PUC de Honduras.</div>' : ''}",
"                \${!id ? '<div class=\"p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700 font-medium\">✨ Se inicializará automáticamente con el PUC de Honduras.</div>' : ''}",
$content
);

$content = str_replace(
"                moneda_base: 'HNL',\r\n                moneda_base: 'HNL',",
"                moneda_base: 'HNL',",
$content
);

// Fallbacks for \n
$content = str_replace(
"    const { value: formValues } = await Swal.fire({\n        e = empresasCache.find(x => x.id == id);\n    }\n\n    const { value: formValues } = await Swal.fire({",
"    const { value: formValues } = await Swal.fire({",
$content
);

$content = str_replace(
"                \${!id ? '<div class=\"p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700 font-medium\">✨ Se inicializará automáticamente con el PUC de Honduras.</div>' : ''}\n                \${!id ? '<div class=\"p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700 font-medium\">✨ Se inicializará automáticamente con el PUC de Honduras.</div>' : ''}",
"                \${!id ? '<div class=\"p-3 bg-blue-50 rounded-lg text-[10px] text-blue-700 font-medium\">✨ Se inicializará automáticamente con el PUC de Honduras.</div>' : ''}",
$content
);

$content = str_replace(
"                moneda_base: 'HNL',\n                moneda_base: 'HNL',",
"                moneda_base: 'HNL',",
$content
);


file_put_contents($file, $content);
echo "Fixed";
