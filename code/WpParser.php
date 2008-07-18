<?php

/*
 * 
 */

class WpParser {
	private $simple_xml;
	private $namespaces;
	private $posts;
	
	public function __construct($filename) {
		$this->simple_xml = simplexml_load_file($filename) or die('Cannot open file.');
		$this->namespaces = $this->simple_xml->getNamespaces(TRUE);
		
	}
	
	// $posts getter
	public function getPosts() {
		return $this->posts;
		
	}
	
	// parse xml in $simple_xml to array of blog posts
	// @return 		array of posts
	public function parse() {
		$sxml = $this->simple_xml;
		$namespaces = $this->namespaces;
		$posts = array();
		
		foreach ($sxml->channel->item as $item) {
			$post = array();
			// get elements in namespaces
			$wp_ns = $item->children($namespaces['wp']);
			$content_ns = $item->children($namespaces['content']);
			$dc_ns = $item->children($namespaces['dc']);
			$wfw_ns = $item->children($namespaces['wfw']);

			$post['Title'] = $item->title;
			$post['Link'] = $item->link;
			$post['Author'] = $dc_ns->creator;
			// post can have more than one category
			$categories = array();
			$tags = '';
			foreach ($item->category as $cat) {
				if (!in_array($cat, $categories)) {
					$categories[] = (string)$cat;
					$tags .= $cat.', ';
				}	
			}
			$tags = substr($tags, 0, strlen($tags)-2);
			$post['Tags'] = $tags;
			
			$post['Content'] = (string)$content_ns->encoded;
			$post['UrlTitle'] = $wp_ns->post_name;
			$post['Date'] = $wp_ns->post_date;
			
			// post can have more than one comment
			$comments = array();
			foreach ($wp_ns->comment as $comment) {
				// comment's properties
				$comment_props = array();
				foreach($comment as $key=>$value) {
					$comment_props[$key] = (string)$value;
				}
				$comments[] = $comment_props;
			}
			$post['Comments'] = $comments;

			$posts[] = $post;
		}
		$this->posts = $posts;
		return $this->posts;
	}
}
?>