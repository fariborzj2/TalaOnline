<?php
/**
 * RSS Feed Service
 */

class RssService {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Fetch news from all active RSS feeds
     */
    public function getLatestNews($limit = 5) {
        if (!$this->pdo) return [];

        $cache_file = __DIR__ . '/../site/uploads/rss_cache.json';
        $cache_time = 600; // 10 minutes

        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
            $cached_data = json_decode(file_get_contents($cache_file), true);
            if ($cached_data) return array_slice($cached_data, 0, $limit);
        }

        try {
            $stmt = $this->pdo->query("SELECT * FROM rss_feeds WHERE is_active = 1 ORDER BY sort_order ASC");
            $feeds = $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }

        if (empty($feeds)) return [];

        $all_news = [];

        foreach ($feeds as $feed) {
            $news_items = $this->fetchRss($feed['url'], $feed['name']);
            $all_news = array_merge($all_news, $news_items);
        }

        // Sort by date descending
        usort($all_news, function($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        if (!empty($all_news)) {
            if (!is_dir(dirname($cache_file))) {
                mkdir(dirname($cache_file), 0755, true);
            }
            file_put_contents($cache_file, json_encode($all_news));
        }

        return array_slice($all_news, 0, $limit);
    }

    /**
     * Fetch and parse a single RSS feed
     */
    private function fetchRss($url, $source_name) {
        $items = [];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return [];

        try {
            $rss = new SimpleXMLElement($response);

            // Handle Atom feeds if necessary, but focus on RSS 2.0
            if (isset($rss->channel->item)) {
                foreach ($rss->channel->item as $item) {
                    $items[] = [
                        'title' => (string)$item->title,
                        'link' => (string)$item->link,
                        'pubDate' => (string)$item->pubDate,
                        'timestamp' => strtotime((string)$item->pubDate),
                        'source' => $source_name,
                        'description' => strip_tags((string)$item->description)
                    ];
                }
            } elseif (isset($rss->entry)) { // Atom
                foreach ($rss->entry as $entry) {
                    $items[] = [
                        'title' => (string)$entry->title,
                        'link' => (string)$entry->link['href'] ?: (string)$entry->id,
                        'pubDate' => (string)$entry->updated,
                        'timestamp' => strtotime((string)$entry->updated),
                        'source' => $source_name,
                        'description' => strip_tags((string)$entry->summary ?: (string)$entry->content)
                    ];
                }
            }
        } catch (Exception $e) {
            // Silently fail if RSS is invalid
        }

        return $items;
    }
}
