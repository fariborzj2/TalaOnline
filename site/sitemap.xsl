<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="2.0"
                xmlns:html="http://www.w3.org/TR/REC-html40"
                xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
                xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml" lang="fa" dir="rtl">
        <head>
            <title>نقشه سایت XML</title>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <style type="text/css">
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                    font-size: 14px;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                    background: #f8fafc;
                }
                .container {
                    max-width: 1000px;
                    margin: 0 auto;
                    background: #fff;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                }
                h1 {
                    color: #1e293b;
                    font-size: 24px;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #e2e8f0;
                    padding-bottom: 10px;
                }
                p {
                    color: #64748b;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                }
                th {
                    background: #f1f5f9;
                    color: #475569;
                    font-weight: bold;
                    text-align: right;
                    padding: 12px;
                    border-bottom: 2px solid #e2e8f0;
                }
                td {
                    padding: 12px;
                    border-bottom: 1px solid #f1f5f9;
                    word-break: break-all;
                }
                tr:hover td {
                    background: #f8fafc;
                }
                a {
                    color: #3b82f6;
                    text-decoration: none;
                }
                a:hover {
                    text-decoration: underline;
                }
                .priority {
                    display: inline-block;
                    padding: 2px 8px;
                    background: #e0f2fe;
                    color: #0369a1;
                    border-radius: 4px;
                    font-size: 12px;
                    font-weight: bold;
                }
                .lastmod {
                    color: #94a3b8;
                    font-size: 12px;
                }
                .logo-img {
                    width: 32px;
                    height: 32px;
                    object-fit: contain;
                    border-radius: 4px;
                    background: #f1f5f9;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>نقشه سایت (Sitemap)</h1>
                <p>این یک فایل XML سیت‌مپ است که برای موتورهای جستجو بهینه شده است.</p>
                <p>تعداد کل آدرس‌ها: <xsl:value-of select="count(sitemap:urlset/sitemap:url)"/></p>

                <table>
                    <thead>
                        <tr>
                            <th width="5%">ردیف</th>
                            <th width="45%">آدرس (URL)</th>
                            <th width="10%">اولویت</th>
                            <th width="15%">تغییرات</th>
                            <th width="25%">آخرین بروزرسانی</th>
                        </tr>
                    </thead>
                    <tbody>
                        <xsl:for-each select="sitemap:urlset/sitemap:url">
                            <tr>
                                <td><xsl:value-of select="position()"/></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px; direction: ltr; justify-content: flex-end;">
                                        <xsl:if test="image:image">
                                            <img src="{image:image/image:loc}" class="logo-img" />
                                        </xsl:if>
                                        <a href="{sitemap:loc}"><xsl:value-of select="sitemap:loc"/></a>
                                    </div>
                                </td>
                                <td>
                                    <span class="priority">
                                        <xsl:value-of select="sitemap:priority"/>
                                    </span>
                                </td>
                                <td>
                                    <xsl:value-of select="sitemap:changefreq"/>
                                </td>
                                <td class="lastmod">
                                    <xsl:value-of select="sitemap:lastmod"/>
                                </td>
                            </tr>
                        </xsl:for-each>
                    </tbody>
                </table>
            </div>
        </body>
        </html>
    </xsl:template>
</xsl:stylesheet>
