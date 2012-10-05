<?php
//    MyDMS. Document Management System
//    Copyright (C) 2002-2005  Markus Westphal
//    Copyright (C) 2006-2008 Malcolm Cowe
//    Copyright (C) 2010 Matteo Lucarelli
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!$user->isAdmin()) {
	UI::exitError(getMLText("admin_tools"),getMLText("access_denied"));
}

UI::htmlStartPage(getMLText("admin_tools"));
UI::globalNavigation();
UI::pageNavigation(getMLText("admin_tools"), "admin_tools");

$categories = $dms->getAllUserKeywordCategories($user->getID());
?>

<script language="JavaScript">
obj = -1;
function showKeywords(selectObj) {
	if (obj != -1)
		obj.style.display = "none";
	
	id = selectObj.options[selectObj.selectedIndex].value;
	if (id == -1)
		return;
	
	obj = document.getElementById("keywords" + id);
	obj.style.display = "";
}
</script>
<?php

UI::contentHeading(getMLText("global_default_keywords"));
UI::contentContainerStart();
?>
	<table>
	<tr>
		<td><?php echo getMLText("selection")?>:</td>
		<td>
			<select onchange="showKeywords(this)" id="selector">
				<option value="-1"><?php echo getMLText("choose_category")?>
				<option value="0"><?php echo getMLText("new_default_keyword_category")?>

				<?php
				
				$selected=0;
				$count=2;				
				foreach ($categories as $category) {
				
					$owner = $category->getOwner();
					if ((!$user->isAdmin()) && ($owner->getID() != $user->getID())) continue;
					
					if (isset($_GET["categoryid"]) && $category->getID()==$_GET["categoryid"]) $selected=$count;				
					print "<option value=\"".$category->getID()."\">" . htmlspecialchars($category->getName());
					$count++;
				}
				?>
			</select>
			&nbsp;&nbsp;
		</td>

		<td id="keywords0" style="display : none;">	
			<form action="../op/op.DefaultKeywords.php" method="post">
  		<?php echo createHiddenFieldWithKey('addcategory'); ?>
			<input type="Hidden" name="action" value="addcategory">
			<?php printMLText("name");?> : <input name="name">
			<input type="Submit" value="<?php printMLText("new_default_keyword_category"); ?>">
			</form>
		</td>
	
	<?php	
	
	foreach ($categories as $category) {
	
		$owner = $category->getOwner();
		if ((!$user->isAdmin()) && ($owner->getID() != $user->getID())) continue;
		
		print "<td id=\"keywords".$category->getID()."\" style=\"display : none;\">";	
	?>
			<table>
				<tr>
					<td colspan="2">
						<form action="../op/op.DefaultKeywords.php" method="post">
  						<?php echo createHiddenFieldWithKey('removecategory'); ?>
							<input type="Hidden" name="action" value="removecategory">
							<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
							<input value="<?php printMLText("rm_default_keyword_category");?>" type="submit" title="<?php echo getMLText("delete")?>">
						</form>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php UI::contentSubHeading("");?>
					</td>
				</tr>				
				<tr>
					<td><?php echo getMLText("name")?>:</td>
					<td>
						<form action="../op/op.DefaultKeywords.php" method="post">
  						<?php echo createHiddenFieldWithKey('editcategory'); ?>
							<input type="Hidden" name="action" value="editcategory">
							<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
							<input name="name" value="<?php echo htmlspecialchars($category->getName()) ?>">&nbsp;
							<input type="Submit" value="<?php printMLText("save");?>">
						</form>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<?php UI::contentSubHeading("");?>
					</td>
				</tr>
				
				<tr>
					<td><?php echo getMLText("default_keywords")?>:</td>
					<td>
						<?php
							$lists = $category->getKeywordLists();
							if (count($lists) == 0)
								print getMLText("no_default_keywords");
							else
								foreach ($lists as $list) {
						?>
									<form style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php" >
  								<?php echo createHiddenFieldWithKey('editkeywords'); ?>
									<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
									<input type="Hidden" name="keywordsid" value="<?php echo $list["id"]?>">
									<input type="Hidden" name="action" value="editkeywords">
									<input name="keywords" value="<?php echo htmlspecialchars($list["keywords"]) ?>">
									<input name="action" value="editkeywords" type="Image" src="images/save.gif" title="<?php echo getMLText("save")?>" style="border: 0px;">
									<!--	 <input name="action" value="removekeywords" type="Image" src="images/del.gif" title="<?php echo getMLText("delete")?>" border="0"> &nbsp; -->
									</form>
									<form style="display: inline-block;" method="post" action="../op/op.DefaultKeywords.php" >
  								<?php echo createHiddenFieldWithKey('removekeywords'); ?>
									<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
									<input type="Hidden" name="keywordsid" value="<?php echo $list["id"]?>">
									<input type="Hidden" name="action" value="removekeywords">
									<input name="action" value="removekeywords" type="Image" src="images/del.gif" title="<?php echo getMLText("delete")?>" style="border: 0px;">
									</form>
									<br>
						<?php }  ?>
					</td>
				</tr>			
				<tr>
					<form action="../op/op.DefaultKeywords.php" method="post">
  				<?php echo createHiddenFieldWithKey('newkeywords'); ?>
					<td><input type="Submit" value="<?php printMLText("new_default_keywords");?>"></td>
					<td>
						<input type="Hidden" name="action" value="newkeywords">
						<input type="Hidden" name="categoryid" value="<?php echo $category->getID()?>">
						<input name="keywords">
					</td>
					</form>
				</tr>
				
			</table>
		</td>
<?php } ?>
	</tr></table>
	
<script language="JavaScript">

sel = document.getElementById("selector");
sel.selectedIndex=<?php print $selected ?>;
showKeywords(sel);

</script>

	
<?php
UI::contentContainerEnd();

UI::htmlEndPage();
?>