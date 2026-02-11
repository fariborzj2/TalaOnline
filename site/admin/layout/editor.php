<?php
$tinymce_key = get_setting('tinymce_api_key', 'no-api-key');
?>
<!-- TinyMCE CDN -->
<script src="https://cdn.tiny.cloud/1/<?= $tinymce_key ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
    /* Force Vazirmatn on TinyMCE UI */
    .tox-tinymce,
    .tox-tinymce *,
    .tox.tox-tinymce-aux,
    .tox.tox-tinymce-aux * {
        font-family: 'Vazirmatn', sans-serif !important;
    }
</style>

<script>
  function initTinyMCE(selector) {
    tinymce.init({
      selector: selector,
      plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
      toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
      directionality: 'rtl',
      language: 'fa',
      height: 500,
      branding: false,
      promotion: false,
      image_advtab: true,
      images_upload_url: 'api/upload_handler.php',
      relative_urls: false,
      remove_script_host: false,
      convert_urls: true,
      content_style: "@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@100..900&display=swap'); body { font-family: Vazirmatn, Arial, sans-serif; font-size: 14px; direction: rtl; }",
      font_family_formats: 'Vazirmatn=Vazirmatn; Arial=arial,helvetica,sans-serif; Courier New=courier new,courier,monospace'
    });
  }
</script>
