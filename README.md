# SmfPhotoResizeMod
A repository for the code and documentation of a "Simple Machines Forum"-modification that allows user to shrink the size of attached photos. The modification strives to meet the demands of administrators and users of the Swebrick.se forum.

## Current state
Current code is a working proof of concept of the idea. It is unknown if it meets the demands of the demands of administrators and users. 

## How it works
For forums users, this modification will give the user the option resize the attached photos to a new smaller size. 

![Proof of concept image](https://github.com/henrikzwomp/SmfPhotoResizeMod/blob/master/modification_poc01.png "Proof of concept image")

When the checkbox is checked, all attached .jpg files will be resized to match the selected maximum width and height. Only newly attached files will be resized. If the user is modifying an older post, already attached photos will not be resized.

If installed as intended, the modification will start with its work once the post is fully made by the SMF code. It won't require to be executed when the post and/or attachments are in a certain state. This will hopefully make the modification easily reinstalled on future editions of SMF. The backside of this solution is that the uploaded files must in their original form pass all the set requirements (such as maximum file size). 

Currently only files that are marked as "image/jpeg" in the database will be resized. This can easily be changed to include other image types.

### Limitations
- Uploaded files needs to fulfill forum rules for attachments such as maximum file size.
- Each theme allowed by the forum might need to be modified for the modification to work.
- Language is hard coded.

## Installation
Copy the file *SwebrickMod_AttachmentResizer.php* to the *Source* folder of the SMF installation. (It doesn't need to be in that folder, but code below assumes it is)

Open up the *Post.php* file found in the Source folder. Go to the end of the function named *Post2*. 

Add the following line before the code that redirects the user to new page. (Look for calls to a function called *redirectexit*). 

```php
include($sourcedir . '/SwebrickMod_AttachmentResizer.php');
```

The code above will include the code that does the photo resizing, but for it to work we need to add new HTML elements to the Post page. 

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

To add it in the same place as in the picture above. Search for *id="moreAttachments"* and add it behind the closing dd-tag following that part.

Note that the resize checkbox and the 800px option are selected by default.
