<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/** @var $_ mixed[]|\OCP\IURLGenerator[] */
/** @var \OC_Defaults $theme */
?>

<div id="app-navigation">
	<ul>
	<?php foreach($_['forms'] as $form) {
		if (isset($form['anchor'])) {
			$anchor = '#' . $form['anchor'];
			$sectionName = $form['section-name'];
			print_unescaped(sprintf("<li><a href='%s'>%s</a></li>", \OCP\Util::sanitizeHTML($anchor), \OCP\Util::sanitizeHTML($sectionName)));
		}
	}?>
	</ul>
</div>

<div id="app-content">

<div id="quota" class="section">
	<div style="width:<?php p($_['usage_relative']);?>%"
		<?php if($_['usage_relative'] > 80): ?> class="quota-warning" <?php endif; ?>>
		<p id="quotatext">
			<?php print_unescaped($l->t('You are using <strong>%s</strong> of <strong>%s</strong>',
			[$_['usage'], $_['total_space']]));?>
		</p>
	</div>
</div>

<div id="personal-settings">
<?php if ($_['enableAvatars']): ?>
<div id="personal-settings-avatar-container">
	<form id="avatar" class="section" method="post" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.postAvatar')); ?>">
		<h2>
			<label><?php p($l->t('Profile picture')); ?></label>
			<span class="icon-loading"/>
		</h2>
	<div id="displayavatar">
		<div class="avatardiv"></div>
		<div class="warning hidden"></div>
		<?php if ($_['avatarChangeSupported']): ?>
		<label for="uploadavatar" class="inlineblock button icon-upload" id="uploadavatarbutton" title="<?php p($l->t('Upload new')); ?>"></label>
		<div class="inlineblock button icon-folder" id="selectavatar" title="<?php p($l->t('Select from Files')); ?>"></div>
		<div class="hidden button icon-delete" id="removeavatar" title="<?php p($l->t('Remove image')); ?>"></div>
		<input type="file" name="files[]" id="uploadavatar" class="hiddenuploadfield">
		<p><em><?php p($l->t('png or jpg, max. 20 MB')); ?></em></p>
		<?php else: ?>
		<?php p($l->t('Picture provided by original account')); ?>
		<?php endif; ?>
	</div>

		<div id="cropper" class="hidden">
			<div class="inlineblock button" id="abortcropperbutton"><?php p($l->t('Cancel')); ?></div>
			<div class="inlineblock button primary" id="sendcropperbutton"><?php p($l->t('Choose as profile picture')); ?></div>
		</div>
		<input type="hidden" id="avatarscope" value="<?php p($_['avatarScope']) ?>">
	</form>
</div>
<?php endif; ?>

<?php
if($_['displayNameChangeSupported']) {
?>
<div id="personal-settings-container">
	<div class="personal-settings-setting-box">
		<form id="displaynameform" class="section">
			<h2>
				<label for="displayname"><?php p($l->t('Full name')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" id="displayname" name="displayname"
			       value="<?php p($_['displayName']) ?>"
			       autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="displaynamescope" value="<?php p($_['displayNameScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="phoneform" class="section">
			<h2>
				<label for="phone"><?php p($l->t('Phone name')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="tel" id="phone" name="phone"
			       value="<?php p($_['phone']) ?>"
			       autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="phonescope" value="<?php p($_['phoneScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="emailform" class="section">
			<h2>
				<label for="email"><?php p($l->t('Email')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="email" name="email" id="email" value="<?php p($_['email']); ?>"
			       placeholder="<?php p($l->t('Your email address')); ?>"
			       autocomplete="on" autocapitalize="off" autocorrect="off" />
			<input type="hidden" id="emailscope" value="<?php p($_['emailScope']) ?>">
			<br />
			<em><?php p($l->t('For password recovery and notifications')); ?></em>
			<span class="icon-checkmark hidden"/>
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="websiteform" class="section">
			<h2>
				<label for="website"><?php p($l->t('Website')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" name="website" id="website" value="<?php p($_['website']); ?>"
			       placeholder="<?php p($l->t('Your website')); ?>"
			       autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="websitescope" value="<?php p($_['websiteScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="addressform" class="section">
			<h2>
				<label for="address"><?php echo $l->t('Address'); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" id="address" name="address"
			       value="<?php p($_['address']) ?>"
			       autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="addressscope" value="<?php p($_['addressScope']) ?>">
		</form>
	</div>
	<span class="msg"></span>
</div>
<?php
} else {
?>
<div id="personal-settings-container" class="no-edit">
	<div id="displaynameform" class="section">
		<h2><?php p($l->t('Full name'));?></h2>
		<span><?php if(isset($_['displayName'][0])) { p($_['displayName']); } else { p($l->t('No display name set')); } ?></span>
	</div>
	<div id="emailform" class="section">
		<h2><?php p($l->t('Email')); ?></h2>
		<span><?php if(isset($_['email'][0])) { p($_['email']); } else { p($l->t('No email address set')); }?></span>
	</div>
	<div id="phoneform" class="section">
		<h2><?php p($l->t('Phone')); ?></h2>
		<span><?php if(isset($_['phone'][0])) { p($_['phone']); } else { p($l->t('No phone number set')); }?></span>
	</div>
	<div id="websiteform" class="section">
		<h2><?php p($l->t('Website')); ?></h2>
		<span><?php if(isset($_['website'][0])) { p($_['website']); } else { p($l->t('No website set')); }?></span>
	</div>
	<div id="addressform" class="section">
		<h2><?php p($l->t('Address')); ?></h2>
		<span><?php if(isset($_['address'][0])) { p($_['address']); } else { p($l->t('No address set')); }?></span>
	</div>
</div>
<?php
}
?>
</div>

<div id="groups" class="section">
	<h2><?php p($l->t('Groups')); ?></h2>
	<p><?php p($l->t('You are member of the following groups:')); ?></p>
	<p>
	<?php p(implode(', ', $_['groups'])); ?>
	</p>
</div>

<?php
if($_['passwordChangeSupported']) {
	script('jquery-showpassword');
?>
<form id="passwordform" class="section">
	<h2 class="inlineblock"><?php p($l->t('Password'));?></h2>
	<div class="hidden icon-checkmark" id="password-changed"></div>
	<div class="hidden" id="password-error"><?php p($l->t('Unable to change your password'));?></div>
	<br>
	<label for="pass1" class="hidden-visually"><?php echo $l->t('Current password');?>: </label>
	<input type="password" id="pass1" name="oldpassword"
		placeholder="<?php echo $l->t('Current password');?>"
		autocomplete="off" autocapitalize="off" autocorrect="off" />
	<label for="pass2" class="hidden-visually"><?php echo $l->t('New password');?>: </label>
	<input type="password" id="pass2" name="personal-password"
		placeholder="<?php echo $l->t('New password');?>"
		data-typetoggle="#personal-show"
		autocomplete="off" autocapitalize="off" autocorrect="off" />
	<input type="checkbox" id="personal-show" name="show" /><label for="personal-show"></label>
	<input id="passwordbutton" type="submit" value="<?php echo $l->t('Change password');?>" />
	<br/>
	<div class="strengthify-wrapper"></div>
</form>
<?php
}
?>

<form id="language" class="section">
	<h2>
		<label for="languageinput"><?php p($l->t('Language'));?></label>
	</h2>
	<select id="languageinput" name="lang" data-placeholder="<?php p($l->t('Language'));?>">
		<option value="<?php p($_['activelanguage']['code']);?>">
			<?php p($_['activelanguage']['name']);?>
		</option>
		<?php foreach($_['commonlanguages'] as $language):?>
			<option value="<?php p($language['code']);?>">
				<?php p($language['name']);?>
			</option>
		<?php endforeach;?>
		<optgroup label="––––––––––"></optgroup>
		<?php foreach($_['languages'] as $language):?>
			<option value="<?php p($language['code']);?>">
				<?php p($language['name']);?>
			</option>
		<?php endforeach;?>
	</select>
	<?php if (OC_Util::getEditionString() === ''): ?>
	<a href="https://www.transifex.com/projects/p/owncloud/"
		target="_blank" rel="noreferrer">
		<em><?php p($l->t('Help translate'));?></em>
	</a>
	<?php endif; ?>
</form>

<div id="sessions" class="section">
	<h2><?php p($l->t('Sessions'));?></h2>
	<span class="hidden-when-empty"><?php p($l->t('These are the web, desktop and mobile clients currently logged in to your ownCloud.'));?></span>
	<table>
		<thead class="token-list-header">
			<tr>
				<th><?php p($l->t('Browser'));?></th>
				<th><?php p($l->t('Most recent activity'));?></th>
				<th></th>
			</tr>
		</thead>
		<tbody class="token-list icon-loading">
		</tbody>
	</table>
</div>

<div id="apppasswords" class="section">
	<h2><?php p($l->t('App passwords'));?></h2>
	<span class="hidden-when-empty"><?php p($l->t("You've linked these apps."));?></span>
	<table>
		<thead class="hidden-when-empty">
			<tr>
				<th><?php p($l->t('Name'));?></th>
				<th><?php p($l->t('Most recent activity'));?></th>
				<th></th>
			</tr>
		</thead>
		<tbody class="token-list icon-loading">
		</tbody>
	</table>
	<p><?php p($l->t('An app password is a passcode that gives an app or device permissions to access your %s account.', [$theme->getName()]));?></p>
	<div id="app-password-form">
		<input id="app-password-name" type="text" placeholder="<?php p($l->t('App name')); ?>">
		<button id="add-app-password" class="button"><?php p($l->t('Create new app password')); ?></button>
	</div>
	<div id="app-password-result" class="hidden">
		<span><?php p($l->t('Use the credentials below to configure your app or device.')); ?></span>
		<div class="app-password-row">
			<span class="app-password-label"><?php p($l->t('Username')); ?></span>
			<input id="new-app-login-name" type="text" readonly="readonly"/>
		</div>
		<div class="app-password-row">
			<span class="app-password-label"><?php p($l->t('Password')); ?></span>
			<input id="new-app-password" type="text" readonly="readonly"/>
			<button id="app-password-hide" class="button"><?php p($l->t('Done')); ?></button>
		</div>
	</div>
</div>

<div id="clientsbox" class="section clientsbox">
	<h2><?php p($l->t('Get the apps to sync your files'));?></h2>
	<a href="<?php p($_['clients']['desktop']); ?>" rel="noreferrer" target="_blank">
		<img src="<?php print_unescaped(image_path('core', 'desktopapp.svg')); ?>"
			alt="<?php p($l->t('Desktop client'));?>" />
	</a>
	<a href="<?php p($_['clients']['android']); ?>" rel="noreferrer" target="_blank">
		<img src="<?php print_unescaped(image_path('core', 'googleplay.png')); ?>"
			alt="<?php p($l->t('Android app'));?>" />
	</a>
	<a href="<?php p($_['clients']['ios']); ?>" rel="noreferrer" target="_blank">
		<img src="<?php print_unescaped(image_path('core', 'appstore.svg')); ?>"
			alt="<?php p($l->t('iOS app'));?>" />
	</a>

	<?php if (OC_Util::getEditionString() === ''): ?>
	<p>
		<?php print_unescaped($l->t('If you want to support the project
		<a href="https://owncloud.org/contribute"
			target="_blank" rel="noreferrer">join development</a>
		or
		<a href="https://owncloud.org/promote"
			target="_blank" rel="noreferrer">spread the word</a>!'));?>
	</p>
	<?php endif; ?>

	<?php if(OC_APP::isEnabled('firstrunwizard')) {?>
	<p><a class="button" href="#" id="showWizard"><?php p($l->t('Show First Run Wizard again'));?></a></p>
	<?php }?>
</div>

<?php foreach($_['forms'] as $form) {
	if (isset($form['form']) and isset($form['form']['page'])) {?>
	<div id="<?php isset($form['anchor']) ? p($form['anchor']) : p('');?>"><?php print_unescaped($form['form']['page']);?></div>
	<?php }
};?>

<div class="section">
	<h2><?php p($l->t('Version'));?></h2>
	<p><a href="<?php print_unescaped($theme->getBaseUrl()); ?>" target="_blank"><?php p($theme->getTitle()); ?></a> <?php p(OC_Util::getHumanVersion()) ?></p>
	<p><?php include('settings.development.notice.php'); ?></p>
</div>

</div>
