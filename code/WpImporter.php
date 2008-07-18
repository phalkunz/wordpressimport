<?php

require('WpParser.php');

class WpImporter extends DataObjectDecorator {

	function updateCMSFields(&$fields) {
		//if ($this->owner->ClassName == 'BlogHolder') {
			$html_str = '<iframe name="WpImport" src="WpImporter_Controller/index/'.$this->owner->ID.'" width="500"> </iframe>';
			$fields->addFieldToTab('Root.Content.Import', new LiteralField("ImportIframe",$html_str));		
		//}
	}
}

class WpImporter_Controller extends Controller {
	// do security/permission check here
	function init() {
		parent::init();
		if(!Permission::check("ADMIN")) Security::permissionFailure();
	}
	
	// required
	function Link() {
		return $this->class .'/';	
	}

	function UploadForm() {
		return new Form($this, "UploadForm", new FieldSet(
			new FileField("XMLFile", 'Wordpress XML file'),
			new HiddenField("BlogHolderID", '', $this->urlParams['ID'])
		), new FieldSet(
			new FormAction('doUpload', 'Import Wordpress XML file')
		));
	}
	
	function doUpload($data, $form) {

		$blogHolderID = $data['BlogHolderID'];
		
		// check is a file is uploaded
		if(is_uploaded_file($_FILES['XMLFile']['tmp_name'])) {
			$file = $_FILES['XMLFile'];
			// check file type. only xml file is allowed
			if ($file['type'] != 'text/xml') {
				echo 'Please select Wordpress XML file';
				die;
			}
			
			$wp = new WpParser($file['tmp_name']);
			$posts = $wp->parse();
			$count = 0;
			foreach ($posts as $post) {
				
				$comments = $post['Comments'];
				
				// create a blog entry
				$entry = new BlogEntry();
				$entry->ParentID = $blogHolderID;
				$entry->update($post);
				$entry->write();
				$entry->publish("Stage", "Live");
				
				// page comment(s)
				foreach ($comments as $comment) {
					$page_comment = new PageComment();
					$page_comment->ParentID = $entry->ID;
					$page_comment->Name = $comment['comment_author'];
					$page_comment->Comment = $comment['comment_content'];
					$page_comment->Created = $comment['comment_date'];
					$page_comment->write();
				}
				// count is used for testing only
				$count++;
				if($count==10) break;
			}
			
			// delete the temporaray uploaded file
			unlink($file['tmp_name']);
			// print sucess message
			echo 'Completed!<br/>';
			echo 'Please refresh the admin page to see the new blog entries.';
		}
		
		
	}

}
?>