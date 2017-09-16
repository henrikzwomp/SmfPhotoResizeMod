# SmfPhotoResizeMod
Repository for code and documentation of a "Simple Machines Forum"-modification that allows user to shrink the size of uploaded photos. The modification strives to meet the demands of administrators and users of the Swebrick.se forum.

## Current state
Current code is a working proof of concept of the idea. It is unknown if it meets the demands of the demands of administrators and users. 

## How it works
For forums users, this modification will give the user the option resize the attached photos to a new smaller size. 

![Proof of concept image](https://github.com/henrikzwomp/SmfPhotoResizeMod/blob/master/modification_poc01.png "Proof of concept image")

If installed as intended, the modification will start its work once the post is fully made. Meaning the uploaded files must in their original form pass all the set requirements (such as file size). This is so it will hopefully will be easy to reinstall modification on future editions of SMF. The modification doesn't require the post and/or attachments to in a certain state. 

Currently only files that are marked as "image/jpeg" in the database will be resized. This can easily be changed to include other image types.

It will only resize newly attached files. If modifying a older post, already attached photos will not be resized.

## Limitations
- Uploaded files needs to fulfill forum rules for attachments such as maximum file size.
- Each theme allowed by the forum needs to be modified for the modification to work.
- Language is hard coded.

## Installation
Copy SwebrickMod_AttachmentResizer.php to the Source folder of the SMF installation. (It doesn't need to be in that folder, but code below assumes it is)

In the Post.php file found in the Source folder. Go to the end of the function named *Post2*. 

Add the following line before the code that redirects the user to new page. (Look for calls to a function called redirectexit). 

```php
include($sourcedir . '/SwebrickMod_AttachmentResizer.php');
```

The code above will include the code that does the photo resizing, but for it to work we need to add new HTML elements to the Post form. 

In the folder for the theme you want to add this modifcation on, open up the file named *Post.template.php*. If it doesn't exists, then you'll need to modify the default theme instead. 

At a suitable place within the Form-tags add the following code. 

```html
<dd class="smalltext" >
  <input name="SB_ResizePhotos" id="SB_ResizePhotos" type="checkbox" value="SB_ResizePhotos" checked >
  <label for="SB_ResizePhotos" >Shrink the size of photos (.jpg) to max width &amp; height of </label>
  <select name="SB_MaxSize">
    <option value="640">640 px</option>
    <option value="800" selected >800 px</option>
    <option value="1024" >1024 px</option>
  </select>
</dd>
```

To add it in the same place as in the picture above. Search for *id="moreAttachments"* and add it behind the closing dd-tag.

Note that the resize checkbox and the 800px option are selected by default.
