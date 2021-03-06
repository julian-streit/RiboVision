<?php require('includes/config.php');

//if logged in redirect to members page
if( $user->is_logged_in() ){
	//header('Location: memberpage.php'); 
	$logged_in_user=$_SESSION['username'];
} else {
	$logged_in_user="guest42";
}

//if form has been submitted process it
if(isset($_POST['submit'])){

	//very basic validation
	if(strlen($_POST['username']) < 3){
		$error[] = 'Username is too short.';
	} else {
		$stmt = $db->prepare('SELECT username FROM members WHERE username = :username');
		$stmt->execute(array(':username' => $_POST['username']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if(!empty($row['username'])){
			$error[] = 'Username provided is already in use.';
		}

	}

	if(strlen($_POST['password']) < 3){
		$error[] = 'Password is too short.';
	}

	if(strlen($_POST['passwordConfirm']) < 3){
		$error[] = 'Confirm password is too short.';
	}

	if($_POST['password'] != $_POST['passwordConfirm']){
		$error[] = 'Passwords do not match.';
	}

	//email validation
	if(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
	    $error[] = 'Please enter a valid email address';
	} else {
		$stmt = $db->prepare('SELECT email FROM members WHERE email = :email');
		$stmt->execute(array(':email' => $_POST['email']));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if(!empty($row['email'])){
			$error[] = 'Email provided is already in use.';
		}

	}


	//if no errors have been created carry on
	if(!isset($error)){

		//hash the password
		$hashedpassword = $user->password_hash($_POST['password'], PASSWORD_BCRYPT);

		//create the activasion code
		$activasion = md5(uniqid(rand(),true));

		try {

			//insert into database with a prepared statement
			$stmt = $db->prepare('INSERT INTO members (username,password,email,active) VALUES (:username, :password, :email, :active)');
			$stmt->execute(array(
				':username' => $_POST['username'],
				':password' => $hashedpassword,
				':email' => $_POST['email'],
				':active' => $activasion
			));
			$id = $db->lastInsertId('memberID');

			//send email
			$to = $_POST['email'];
			$subject = "Registration Confirmation";
			$body = "<p>Thank you for registering at demo site.</p>
			<p>To activate your account, please click on this link: <a href='".DIR."activate.php?x=$id&y=$activasion'>".DIR."activate.php?x=$id&y=$activasion</a></p>
			<p>Regards Site Admin</p>";

			$mail = new Mail();
			$mail->setFrom(SITEEMAIL);
			$mail->addAddress($to);
			$mail->subject($subject);
			$mail->body($body);
			$mail->send();

			//redirect to index page
			//header('Location: index.php?action=joined');
			//exit;

		//else catch the exception and show the error.
		} catch(PDOException $e) {
		    $error[] = $e->getMessage();
		}

	}

}

//define page title
$title = 'Demo';

//include header template
require('layout/header.php');

include('includes/ngl_include.php');
//include('includes/jmol_include.php');

?>

<div id="menu" class="ui-widget-content">
        <div id="SiteInfo" class="ui-widget-content">
            <h3 class="ui-widget-header2"><a href="./ChangeLog.txt" target="_blank" style="color:#FFF">RiboVision: Version 1.9.0</a></h3>
            <div id="MainLogoDiv">
			<a href="./Documentation/index.html" target="_blank"><img src="images/RiboVisionLogo-01.png" id="MainLogo" title="RiboVision Manual" alt="RiboVision Logo" /></a>
			Click for Manual. Welcome , </br><a id="loginbutton" href="#"><?php echo $logged_in_user ?> </a>
			</div>
         
		</div>

        <div id="MainMenu" class="ui-widget-content">
            <h3 class="ui-widget-header2">Select</h3>

            <div id="SideBarAccordian">
                <h3><a href="#">Species/Subunit</a></h3>
                <div id="SpeciesDiv" style="padding:0.1em">
					<!--
					Molecule Position:
					<div id="SubUnitNumberToggle">
						<input id="LoadSubunit1" type="radio" name="LoadSubunit" value="on" /><label for="LoadSubunit1">1st</label>
						<input id="LoadSubunit2" type="radio" name="LoadSubunit" value="off" /><label for="LoadSubunit2">2nd</label>
					</div>
					Built-In Structures:
					<ul id="speciesList" style="font-size:0.9em">
                        <li class="ui-menu-item ios-menu-back-link" role="menu">No Molecule</li>
                    </ul>//-->
					Built-In Structures:
					<select id="speciesList" multiple="multiple" > </select>
					
					<div id="ImportStrutureFileDiv" style="padding:0.1em">
						Custom Structures:
						Import Structure: <input type="file" id="importstructurefile" name="importstructurefile[]" accept="text/csv" /> <output id="structurefilelist"></output><br />
					</div>
					<div id="ptModToggle">
					<br /><br />
					Post-transcriptional Modifications:<br />
					<input id="ptON" type="radio" name="ptmod" value="on" /><label for="ptON">On</label> <input id="ptOFF" type="radio" name="ptmod" value=
						"off" /><label for="ptOFF">Off</label>
					</div>
				
                </div>
				
				
                <h3><a href="#">Select</a></h3>

                <div id="SelectionDiv" style="padding:0.1em">
                    Nucleotides: e.g. 5S:(1-10)<br />
                    <input id="commandline" type="text" onkeydown="if (event.keyCode == 13) document.getElementById(&#39;commandSelect&#39;).click();" /><br />
                    <input id="commandSelect" name="selebutton" type="button" onclick="JavaScript:commandSelect();" value="Apply Selection 2D" /><br />
                    <input id="select3d" name="selebutton" type="button" onclick="JavaScript:commandSelect();updateModel();" value=
                    "Apply Selection 3D" /><br />
                    <input id="clearselection" name="selebutton" type="button" onclick="JavaScript:clearSelection();" value="Clear Selection" /><br />
                    <br />
                    Select domains or helices:<br />
                    <select id="selectByDomainHelix" name="SelectByDomainHelix" multiple="multiple">
                        </select><br />
                </div>

                <h3><a href="#">Nucleotide Data</a></h3>

                <div id="StructDataDiv" style="padding:0.1em">
                    <span id="StructDataLabel"><a class="ManualLink" href="./Documentation/StructuralData.html" target="_blank">Help?</a></span><br />
                    <br />

                    <div id="StructDataBubbles">
					</div>
                </div>

                <h3><a href="#">Phylogeny Data</a></h3>

                <div id="AlnDiv" style="padding:0.1em">
                    <span id="AlnLabel"><a class="ManualLink" href="./Documentation/AlignmentData.html" target="_blank">Help?</a></span><br />
                    <br />

                    <div id="AlnBubbles"></div>
                </div>

                <h3><a href="#">Protein Contacts</a></h3>

                <div id="ProtDiv" style="padding:0.1em">
                    <span id="ProtLabel"><a class="ManualLink" href="./Documentation/ProteinData.html" target="_blank">Help?</a></span><br />
                    <br />
                    <select id="ProtList" multiple="multiple">
                        </select><br />
                    <br />

                    <div id="ProteinBubbles"></div>
                </div>

                <h3><a href="#">Inter-Nucleotide Contacts</a></h3>

                <div id="InteractDataDiv" style="padding:0.1em">
                    <span id="InteractionLabel"><a class="ManualLink" href="./Documentation/InteractionData.html" target="_blank">Help?</a></span><br />
                    <br />

                    <div id="primaryInteractData">
                        Interaction Type:<br />
                        <select id="PrimaryInteractionList" multiple="multiple" onchange="JavaScript:refreshBasePairs(value);">
                            </select>
                    </div>

                    <div id="secondaryInteractData" style="margin-top:10px">
                        Interaction Sub-Type:<br />
                        <select id="SecondaryInteractionList" multiple="multiple">
                            </select>
                    </div>

                    <p class="DataDescription"></p>
                </div>

                <h3><a href="#">Import</a></h3>

                <div id="ImportDataFileDiv" style="padding:0.1em">
                    <span id="ImportDataLabel"><a class="ManualLink" href="./Documentation/UserDataSyntax.html" target="_blank">Help?</a></span><br />
                    <br />
                    <a id="TemplateLink" href="#" target="_blank">Download Data Template</a><br />
                    <br />
                    Import Data: <input type="file" id="importdatafile" name="files[]" accept="text/csv" /> <output id="list"></output><br />

                    <div id="CustomDataBubbles"></div>

                    <p class="DataDescription"></p>
                </div>
            </div>
        </div>

        <div id="MiniLayer" class="ui-widget-content">
            <h3 class="ui-widget-header2">Display</h3>
			<h3 id="MiniLayerLabel" class="ui-widget-header3">2D Layers</h3>
			<input type="button" id="MiniOpenLayerBtn" title="Open Layer Manager" value="Edit Layers" style="margin: auto;" /><br />
			
        </div>
		<div id="LinkSection" class="ui-widget-content">
			<h3 class="ui-widget-header3">3D Panel</h3>
			</span>
		</div>
        <div id="ExportData" class="ui-widget-content">
            <h3 class="ui-widget-header2">Save</h3>
			<input id="SaveEverythingBtn" type="button" value="Save Manager" title="Save your figures, sequences, data, and session."/>
			
        </div>
    </div>

    <div id="LogoDiv" class="ui-widget-content">
		<a href="http://astrobiology.gatech.edu/" target="_blank"><img src="images/RiboEvoLogo.png" class="ComboLogo" title="Center for Ribosomal Origins and Evolution (Ribo Evo)" alt="RiboEvo Logo" /></a>
		<a href="http://astrobiology.nasa.gov/nai/" target="_blank"><img src="images/NASALogo.png" class="ComboLogo" title="NASA Astrobiology Institute (NAI)" alt="NASA Logo" /></a>
	</div>

    <div id="navigator" style="z-index:999; width:65px">
		<div id="compassImgs"><img class="compass" src="images/compass/navigator1_01.png" alt="R" onclick="JavaScript:resetView()" /> <img title="Pan up"
        class="compass" src="images/compass/navigator1_02.png" alt="U" onclick="JavaScript:pan(0,-20)" /> <img class="compass" src=
        "images/compass/navigator1_03.png" alt="C" onclick="JavaScript:resetView()" /> <img title="Pan left" class="compass" src=
        "images/compass/navigator1_04.png" alt="L" onclick="JavaScript:pan(-20,0)" /> <img title="Return to center" class="compass" src=
        "images/compass/navigator1_05.png" alt="R" onclick="JavaScript:resetView()" /> <img title="Pan right" class="compass" src=
        "images/compass/navigator1_06.png" alt="R" onclick="JavaScript:pan(20,0)" /> <img class="compass" src="images/compass/navigator1_07.png" alt="R"
        onclick="JavaScript:resetView()" /> <img title="Pan down" class="compass" src="images/compass/navigator1_08.png" alt="D" onclick=
        "JavaScript:pan(0,20)" /> <img class="compass" src="images/compass/navigator1_09.png" alt="R" onclick="JavaScript:resetView()" /></div>

        <div id="slider"></div>
    </div>

    <div id="canvasDiv" oncontextmenu="return false" class="ui-widget-content">
		<span id="canvaslabel" class="PanelLabels">2D Panel</span>
		<canvas id="HighlightLayer_0" style="z-index:998; width:100%">Your browser does not support Canvas and can not use this site. We
				recommend Firefox. Internet Explorer users need to be at least on version 9, which requires Windows Vista or higher. Internet Explorer 10 (Windows 7 or
				higher) is needed for all features to work. Check out <a href="http://caniuse.com/#feat=canvas" target="_blank">this nice table of browser
				support.</a>. Also, in order to use Custom Data Feature, <a href="http://caniuse.com/#feat=filereader" target="_blank">check out this
				table.</a></canvas>
        <p id="nocanvas"></p>
    </div>

    <div id="topMenu" class="ui-widget-content">
        <div id="NavLineDiv"></div>
		<span class="PanelLabels">1D Panel</span>
    </div>

    <div id="the3DpanelDiv" class="ui-widget-content">
	 <span class="PanelLabels">3D Panel</span>
	</div>

    <div id="toolBar">
        <button id="openLayerBtn" class="toolBarBtn" title="Open Layer Manager"></button><br />
        <button id="openSelectionBtn" class="toolBarBtn" title="Open Selection Manager"></button><br />
        <button id="openColorBtn" class="toolBarBtn" title="Open Color Picker"></button><br />
        <button id="SelectionMode" class="toolBarBtn" title="No function yet"></button><br />
		<button id="Extra3Dmenus" class="toolBarBtn" title="Additional 3D Panel Options"></button><br />
        <button id="RiboVisionSettings" class="toolBarBtn" title="RiboVision Settings"></button><br />
        <button id="RiboVisionSaveManager" class="toolBarBtn" title="RiboVision Save/Restore Manager"></button><br />
        <button id="openInteractionSettingBtn" class="toolBarBtn" title="Interaction Setting"></button><br />
        <button id="openManualBtn" class="toolBarBtn" title="Ribovision Manual" onclick="window.open(&#39;./Documentation/index.html&#39;)">Ribovision
        Manual</button>
    </div><!--Layer Control Dialog Box-->

    <div id="LayerDialog" title="Layer / Selection Manager">
        <div id="PanelTabs">
            <ul>
                <li>
                    <a href="#LayerPanel">Layers</a>
                </li>

                <li>
                    <a href="#SelectionPanel">Selections</a>
                </li>
            </ul>

            <div id="LayerPanel"></div>

            <div id="SelectionPanel"></div>
        </div>
    </div>

    <div id="LayerPreferenceDialog" title="Layer Preferences">
        <label for="layerNameInput"><b>Layer Name:</b></label> <input type="text" id="layerNameInput" value="" name="layerNameInput" />

        <div id="LayerColorDiv">
            <b>Layer Color:</b> <input id="layerColor" type="text" name="color" value="#940B06" />

            <div id="layerColorPicker"></div>
        </div>
    </div>

    <div id="SelectionPreferenceDialog" title="Selection Preferences">
        <label for="selectionNameInput">Selection Name:</label> <input type="text" id="selectionNameInput" value="" name="selectionNameInput" /> <input id=
        "selectionNameChangeBtn" type="button" value="Change" onclick="Javascript:changeCurrentSelectionName()" />

        <div id="SelectionColorDiv">
            Layer Color: <input id="selectionColor" type="text" name="color" value="#940B06" /> <input id="selectionColorChangeBtn" type="button" value=
            "Change" onclick="Javascript:changeSelectionColor()" />

            <div id="selectionColorPicker"></div>
        </div>
    </div><!--Color Control Dialog Box-->

    <div id="ColorDialog" title="Nucleotide Coloring">
        <div id="ColorDiv">
            Pick Color:<br />
            <input id="MainColor" type="text" name="color" value="#940B06" /><br />

            <div id="colorpicker"></div><input id="clearColor" type="button" name="clearColor" onclick="Javascript:clearColor(true)" value="Clear Color" />
            <input id="colorSelection" type="button" onclick="Javascript:colorSelection()" value="Color Selection" /> <input id="clearselection2" name=
            "selebutton" type="button" onclick="JavaScript:clearSelection();" value="Clear Selection" />
        </div>
    </div>

    <div id="InteractionSettingDialog" title="Interaction Setting">
		<div id="ColorDiv3">
            Pick Color:<br />
            <input id="LineColor" type="text" name="color" value="#940B06" /><br />

            <div id="colorpicker3"></div>
			<!--<input id="clearColor" type="button" name="clearColor" onclick="Javascript:clearColor(true)" value="Clear Color" 
            <input id="colorLineSelection" type="button" onclick="Javascript:colorLineSelection()" value="Color Line Selection" />
			<input id="clearLineSelection" name="selebutton" type="button" onclick="JavaScript:clearLineSelection();" value="Clear Line Selection" />/>-->
        </div>
        <div class="dialogContentArea">
            <!--
			<p style="line-height:1.6em">Choose single line or multiple ones:</p>

            <div id="singleMultiChoice">
                <input type="radio" id="radio1" name="radio" /><label for="radio1">Single</label> <input type="radio" id="radio2" name="radio" checked=
                "checked" /><label for="radio2">Multiple</label>
            </div><br />-->

            <p id="lineOpacity" style="line-height:1.6em">Line Opacity: 50%</p>

            <div id="lineOpacitySlider"></div>
        </div>
    </div>

    <div id="RiboVisionSettingsPanel" title="Settings Panel">
        <div id="OverAllControlDiv" class="ui-widget-content SettingsDiv">
            <h3 class="ui-widget-header3">General Settings</h3>

            <div id="buttonmode">
                One Button Mode:<br />
                <input id="moveMode" type="radio" name="mode" value="move" onclick="modeSelect(&#39;move&#39;)" /><label for="moveMode">Move</label><br />
                <input id="selectMode" type="radio" name="mode" value="select" onclick="modeSelect(&#39;select&#39;)" /><label for="selectMode">Select
                Nucleotides</label><br />
                <input id="selectModeL" type="radio" name="mode" value="selectL" onclick="modeSelect(&#39;selectL&#39;)" /><label for="selectModeL">Select
                Lines</label><br />
                <input id="colorMode" type="radio" name="mode" value="color" onclick="modeSelect(&#39;color&#39;)" /><label for="colorMode">Color
                Nucleotides</label><br />
				 <input id="colorModeL" type="radio" name="mode" value="color" onclick="modeSelect(&#39;colorL&#39;)" /><label for="colorModeL">Color
                Lines</label><br />
            </div><br />

            <p>Resize 2D &amp; 3D panels</p>

            <div id="canvasPorportionSlider" style="width:240px;"></div><br />

            <p>Resize 1D Panel</p>

            <div id="topPorportionSlider" style="width:13px;"></div>
        </div>

        <div id="NLControlDiv" class="ui-widget-content SettingsDiv">
            <h3 class="ui-widget-header3">1D Panel</h3>

            <div id="NavLineToggle">
                1D Panel:<br />
                <input id="nlON" type="radio" name="nl" value="on" /><label for="nlON">On</label> <input id="nlOFF" type="radio" name="nl" value=
                "off" /><label for="nlOFF">Off</label>
            </div>
        </div>

        <div id="SSControlDiv" class="ui-widget-content SettingsDiv">
            <h3 class="ui-widget-header3">2D Panel</h3>

            <div id="ResidueTipToggle">
                ResidueTip:<br />
                <input id="rtON" type="radio" name="rt" value="on" /><label for="rtON">On</label> <input id="rtOFF" type="radio" name="rt" value=
                "off" /><label for="rtOFF">Off</label>
            </div>
			<br>	
            <div id="ZAset">
                Zoom-Aware Lines:<br />
                <input id="zaON" type="radio" name="za" value="on" /><label for="zaON">On</label> <input id="zaOFF" type="radio" name="za" value=
                "off" /><label for="zaOFF">Off</label>
            </div>
        </div>

        <div id="JmolControlDiv" class="ui-widget-content SettingsDiv">
            <h3 class="ui-widget-header3">3D Panel</h3>

            <p>Jmol Panel can be turned on and off. The regular Java version of Jmol is recommened. The HTML5/JavaScript version of Jmol, called
            JSmol is also available. </p>

            <div id="JmolToggle">
                3D Panel:<br />
                <input id="jpON" type="radio" name="jp" value="on" /><label for="jpON">On</label> <input id="jpOFF" type="radio" name="jp" value=
                "off" /><label for="jpOFF">Off</label>
            </div>

            <div id="JmolTypeToggle">
                Jmol Type:<br />
                <input id="JmolJava" type="radio" name="jjsmol" value="Java" /><label for="JmolJava">Jmol (Java)</label> <input id="JSmolJS" type="radio" name=
                "jjsmol" value="JS" /><label for="JSmolJS">JSmol (no Java)</label>
            </div>

            <div id="BaseView">
                BaseView Mode:<br />
                <input id="bvON" type="radio" name="bv" value="on" /><label for="bvON">On</label> <input id="bvOFF" type="radio" name="bv" value=
                "off" /><label for="bvOFF">Off</label>
            </div><br />
            <br />
            <input id="SetDefaultJmolType" type="button" name="sdjt" value="Set default Jmol Type" />
        </div>
    </div>

    <div id="dialog-confirm-delete" title="Delete the currently selected layer?">
        <p>Replace Me Text.</p>
    </div>

    <div id="dialog-confirm-delete-S" title="Delete the currently selected selection?">
        <p>Replace Me Text.</p>
    </div>

    <div id="dialog-selection-warning" title="No layers are currently selected!">
        <p>Replace Me Text.</p>
    </div>

    <div id="dialog-invalid-color-error" title="Invalid color name. Try again.">
        <p>Sorry, the color you have entered is invalid. It is neither a valid &quot;Hex Code&quot; nor a valid HTML5 color name. Please try again.</p>
    </div>

    <div id="dialog-unique-layer-error" title="This name is taken. Try again.">
        <p>Sorry, all layer names must be unique. The name you entered already exists. Please try again.</p>
    </div>

    <div id="dialog-unique-selection-error" title="This name is taken. Try again.">
        <p>Sorry, all selection names must be unique. The name you entered already exists. Please try again.</p>
    </div>

    <div id="dialog-layer-type-error" title="Layer type mismatch">
        <p>You attempted to put nucleotide data into an incompatible layer. Please select a circle, residue, or contour layer. You may create a new circle or contour layer if you like.</p>
    </div>

    <div id="dialog-name-error" title="Name not allowed">
        <p>This name is not allowed under the current naming rules. Names must begin with a letter and cannot exceed 16 characters. Only letters, digits
        ([0-9]), hyphens (&quot;-&quot;), underscores (&quot;_&quot;), colons (&quot;:&quot;), and periods (&quot;.&quot;) are allowed.</p>
    </div>

    <div id="dialog-generic-notice" title="Notice to User">
        <p class="ReplaceText"></p>
    </div>

    <div id="dialog-restore-state" title="Restore a state?">
        <p>Choose file.</p>Load Data Set: <input type="file" id="files2" name="files[]" accept="text/txt" /> <output id="list2"></output>
    </div>

    <div id="ResidueTip" title="ToolTip" style="position:fixed;width:1px;height:1px;z-index:1000000">
		<div id="residuetip" style="visibility:hidden">
            <h3 class="ui-widget-header3">ResidueTip</h3>
			<div id="ResidueTipContent">
				<span id="resName" class="NavLineItem2"></span>
				<span id="conSeqLetter" class="NavLineItem"></span>
				<span id="activeData" class="NavLineItem"></span>
				<span id="conPercentage" class="NavLineItem"></span>
			</div>
        </div>
    </div>

    <div id="InteractionTip" title="ToolTip" style="position:fixed;width:1px;height:1px;z-index:1000000">
		<div id="interactiontip" style="visibility:hidden"> 
			<h3 class="ui-widget-header3">ResidueTip</h3>
            <span id="BasePairType" class="NavLineItem2"></span>
            <span id="BasePairSubType" class="NavLineItem2"></span>
			<div id="iResidueTipA"></div>
			<div id="iResidueTipB"></div>
        </div>
    </div>

    <div id="dialog-Jmol-Type" title="Choose Viewing Option">
        <!--<p>We recommend using <span id="JmolType"></span> on this computer/device. Which would you like to try? You can change your decision at any time in the
        RiboVision Settings Panel (gear icon).</p>//-->
		<p>RiboVision can be used to view/analyze secondary structures alone or secondary structures in combination with 3D structures.</p>
        <div id="JmolTypeToggle2">
			<input id="JMolDisabled" type="radio" name="jjsmol2" value="Disabled" /><label for="JMolDisabled">Click here for secondary structures alone<br /> (works in most browsers, including tablets/phones)</label>
			<input id="JmolJava2" type="radio" name="jjsmol2" value="Java" /><label for="JmolJava2">Click here for secondary structures in combination<br /> with 3D structures (<b>recommended</b>, requires java)</label><br />
			<!--<input id="JSmolJS2" type="radio" name="jjsmol2" value="JS" /><label for="JSmolJS2">JSmol (no Java)</label> <br />//-->
        </div><br />
        <input id="RememberJmol" type="checkbox" /><label for="RememberJmol">Remember this preference.</label>
    </div>

    <div id="Privacy-confirm" title="Privacy Policy"></div>

    <div id="dialog-saveEverything" title="Save Manager">
		<div id="SaveTabs" title="SaveTabs">
			<ul>
				<li>
					<a href="#Tab-saveFigures">Figures</a>
				</li>

				<li>
					<a href="#Tab-saveSeqData">Sequences & Data</a>
				</li>
				
				<li>
					<a href="#Tab-saveManager">Session</a>
				</li>
				
			</ul>

			<div id="Tab-saveFigures" title="Save / Export Figures">
				<p>RiboVision saves images from each panel separately.</p>

				<div id="NavLineExport" class="ui-widget-content ExportDiv3">
					<h3 class="ui-widget-header3">1D Panel</h3>

					<p>The 1D Panel can be saved as an SVG.</p>
					<input type="button" onclick="JavaScript:saveNavLine();" value="Save as SVG" /><br />
				</div>

				<div id="CanvasExport" class="ui-widget-content ExportDiv3">
					<h3 class="ui-widget-header3">2D Panel</h3>

					<p>Would you like to export just the visible layers, or all layers?</p><input id="visibleLayers" type="radio" name="savelayers" value="visible"
					checked="checked" /><label for="visibleLayers">Visible Layers</label> <input id="allLayers" type="radio" name="savelayers" value=
					"all" /><label for="allLayers">All Layers</label>

					<p>SVG files are best for further editing.</p><input id="saveSVG-btn" type="button" value="Save as SVG" /><br />

					<p>PDF files are best for printing and sharing.</p><input id="savePDF-btn" type="button" value="Save as PDF" /> <br>
					
					<p>PNG and JPG files are good for presentations. PNG has the advantage of a transparent background.</p>
					<input id="savePNG-btn" type="button" value="Save as PNG" />
					<input id="saveJPG-btn" type="button" value="Save as JPG" />
				</div>

				<div id="JmolExport" class="ui-widget-content ExportDiv3">
					<h3 class="ui-widget-header3">3D Panel</h3>
					<p>Jmol has built-in image saving capabilities.</p>
					<p>Alternatively, quick save the 3D Panel as a PNG.</p>
					<input id="save3dImg-btn" type="button" value="Save as PNG" /><br />
					<p>Additionally, you may export a PyMOL Script for further processing in PyMOL.</p>
					<input id="savePML-btn" type="button" value="PyMOL Script" />
				</div>
			</div>
			
			<div id="Tab-saveSeqData" title="Save / Export Sequences and Data">
				<div id="SequenceExport" class="ui-widget-content ExportDiv2">
					<h3 class="ui-widget-header3">Plain Sequences</h3>
					<p>Sequences (including partial sequences from selections) can be exported in a standard fasta format.</p>
					<input type="button" onclick="JavaScript:saveFasta();" value="Save Sequence(s) as Text (.fasta)" />
					<p>Sequences (including partial sequences from selections) can be exported to native RiboVision format. These files
					 contain nucleotide numbers and optionally can be imported into RiboVison using the <a href="./Documentation/UserDataSyntax.html" target="_blank">Import</a> function.</p>
					<input type="button" onclick="JavaScript:saveSeqTable();" value="Save Sequence(s) as Table (.csv)" />
				</div>
				<div id="DataExport" class="ui-widget-content ExportDiv2">
					<h3 class="ui-widget-header3">Data & Sequences</h3>
					<p>Sequences can be saved along with data from all layers in native RiboVision format.</p> 
					<input type="button" onclick="JavaScript:saveSeqDataTable();" value="Save Nucleotide Data as Table (.csv)" />
					<p>Nucleotide-Nucleotide Interactions can also be exported to native RiboVision format. 
					These files can further be imported into RiboVision using the <a href="./Documentation/UserDataSyntax.html" target="_blank">Import</a> function.</p>
					<input type="button" onclick="JavaScript:saveInteractionDataTable();" value="Save Interaction Data as Table (.csv)" />
				</div>
			</div>
			
			 <div id="Tab-saveManager" title="Save/Restore Manager">
				<div id="FreshDiv" class="ui-widget-content">
				   <h3 class="ui-widget-header3">Restore Default State</h3>
				   <input id="freshenRvState" name="savebuttonT" type="button" value="Restore" />
				</div>

				<div id="SaveOptionsDiv" class="ui-widget-content">
					All Locations:<br />
					<input id="LastSpeciesCheck" name="LastSpeciesCheck" value="LastSpeciesCheck" type="checkbox" checked="checked" /><label for=
					"LastSpeciesCheck">Current Species Loaded</label><br />
					<input id="LayersCheck" name="LayersCheck" value="LayersCheck" type="checkbox" checked="checked" /><label for="LayersCheck">Layers with
					data</label><br />
					<input id="SelectionsCheck" name="SelectionsCheck" value="SelectionsCheck" type="checkbox" checked="checked" /><label for=
					"SelectionsCheck">Selections</label><br />
					<input id="MouseModeCheck" name="MouseModeCheck" value="MouseModeCheck" type="checkbox" checked="checked" /><label for="MouseModeCheck">Mouse
					Mode</label><br />
					<input id="PanelSizesCheck" name="PanelSizesCheck" value="PanelSizesCheck" type="checkbox" checked="checked" /><label for="PanelSizesCheck">Panel
					Divider Settings</label><br />
					<input id="CanvasOrientationCheck" name="CanvasOrientationCheck" value="CanvasOrientationCheck" type="checkbox" checked="checked" /><label for=
					"CanvasOrientationCheck">2D Settings</label><br />
					<input id="JmolOrientationCheck" name="JmolOrientationCheck" value="JmolOrientationCheck" type="checkbox" checked="checked" /><label for=
					"JmolOrientationCheck">3D Settings</label><br />
					<br />
					File / Server only:<br />
					<input id="WholeDataSetCheck" name="WholeDataSetCheck" value="WholeDataSetCheck" type="checkbox" checked="checked" /><label for=
					"WholeDataSetCheck">Entire DataSet</label><br />
					<br />
				</div>

				<div id="SaveControlDivB" class="ui-widget-content">
					<h3 class="ui-widget-header3">Save in Browser</h3>
					<div id="SaveControlB">
						<!--
						Save To / Restore From:<br />
						<br />
						<input id="sscB" type="radio" name="ssc" value="LocalStorage" /><label for="sscB">Browser</label> <input id="sscF" type="radio" name="ssc"
						value="File" /><label for="sscF">File</label> <input id="sscS" type="radio" name="ssc" value="Server" /><label for="sscS">Server</label><br />
						<br />//-->
						Session Name:<br />
						<input id="SaveStateFileName" name="SaveStateFileName" type="text" value="RV_DataSet1" /><br /><br />
						<!--
						<p>Warning! Save to &quot;Server&quot; is currently being provided for testing purposes only. Data can be overwritten and/or deleted at any
						time. Additionally, other people may be able to see your work. Don&#39;t rely on this feature right now, keep backups in files.</p>//-->
						<input id="ssSaveB" type="button" value="Save" /> 
						<input id="ssRestoreB" type="button" value="Restore" /><br /><br />
						<input id="clearLS" type="button" value="Delete all Sessions" /> <br /><br />
						<b>Saved Sessions:</b><br />
						<div id="SessionListDiv">
							<p id="SessionList"></p><br /><br />
						</div>
						
						
					</div>
				</div>
				<div id="SaveControlDivF" class="ui-widget-content">
					<h3 class="ui-widget-header3">Save to File</h3>
					<div id="SaveControlF">
						<!--
						Save To / Restore From:<br />
						<br />
						<input id="sscB" type="radio" name="ssc" value="LocalStorage" /><label for="sscB">Browser</label> <input id="sscF" type="radio" name="ssc"
						value="File" /><label for="sscF">File</label> <input id="sscS" type="radio" name="ssc" value="Server" /><label for="sscS">Server</label><br />
						<br />//-->
						Session Name:<br />
						<input id="SaveStateFileName" name="SaveStateFileName" type="text" value="RV_DataSet1" /><br /><br />
						<!--
						<p>Warning! Save to &quot;Server&quot; is currently being provided for testing purposes only. Data can be overwritten and/or deleted at any
						time. Additionally, other people may be able to see your work. Don&#39;t rely on this feature right now, keep backups in files.</p>//-->
						<input id="ssSaveF" type="button" value="Save" /> 
						<input id="ssRestoreF" type="button" value="Restore" />
						<p>We can not support restoring directly from zip files. Please unzip your RiboVision Session before restoring.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
    <div id="dialog-addLayer" title="Create a New Layer">
        <b>Layer Name:</b> <input id="newLayerName" name="newLayerName" type="text" />

        <div id="LayerColorDiv2">
            <b>Layer Color:</b> <input id="layerColor2" type="text" name="color" value="#940B06" />

            <div id="layerColorPicker2"></div>
        </div>
    </div>

    <div id="dialog-addSelection" title="Create a New Selection">
        <input id="newSelectionName" name="newSelectionName" type="text" />

        <div id="selectionColorDiv2">
            Selection Color: <input id="selectionColor2" type="text" name="color" value="#940B06" />

            <div id="selectionColorPicker2"></div>
        </div>
    </div>
	<div id="dialog-extra3Dmenus" title="Extra 3D Panel Features">
	 
	</div>
	<div id="dialog-login" title="Sign in for Advanced Features">
	<div class="container">

	<div class="row">

	    <div class="col-xs-12 col-sm-8 col-md-6 col-sm-offset-2 col-md-offset-3">
			<form role="form" method="post" action="" autocomplete="off">
				
				
				
				
				<h2>Please Sign Up</h2>
				<p>Already a member? <a href='login.php'>Login</a></p>
				<hr>

				<?php
				//check for any errors
				if(isset($error)){
					foreach($error as $error){
						echo '<p class="bg-danger">'.$error.'</p>';
					}
				}

				//if action is joined show sucess
				if(isset($_GET['action']) && $_GET['action'] == 'joined'){
					echo "<h2 class='bg-success'>Registration successful, please check your email to activate your account.</h2>";
				}
				?>

				<div class="form-group">
					<input type="text" name="username" id="username" class="form-control input-lg" placeholder="User Name" value="<?php if(isset($error)){ echo $_POST['username']; } ?>" tabindex="1">
				</div>
				<div class="form-group">
					<input type="email" name="email" id="email" class="form-control input-lg" placeholder="Email Address" value="<?php if(isset($error)){ echo $_POST['email']; } ?>" tabindex="2">
				</div>
				<div class="row">
					<div class="col-xs-6 col-sm-6 col-md-6">
						<div class="form-group">
							<input type="password" name="password" id="password" class="form-control input-lg" placeholder="Password" tabindex="3">
						</div>
					</div>
					<div class="col-xs-6 col-sm-6 col-md-6">
						<div class="form-group">
							<input type="password" name="passwordConfirm" id="passwordConfirm" class="form-control input-lg" placeholder="Confirm Password" tabindex="4">
						</div>
					</div>
				</div>

				<div class="row">
					<div class="col-xs-6 col-md-6"><input type="submit" name="submit" value="Register" class="btn btn-primary btn-block btn-lg" tabindex="5"></div>
				</div>
			</form>
		</div>
	</div>
    </div>

	


</div>

<?php
//include header template
require('layout/footer.php');
?>