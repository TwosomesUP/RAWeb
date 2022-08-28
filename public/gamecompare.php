<?php

use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Unregistered)) {
    abort(401);
}

$gameID = requestInputSanitized('ID', null, 'integer');
$user2 = requestInputSanitized('f');

$totalFriends = getAllFriendsProgress($user, $gameID, $friendScores);

$numAchievements = getGameMetadata($gameID, $user, $achievementData, $gameData, 0, $user2);

$consoleID = $gameData['ConsoleID'];
$consoleName = $gameData['ConsoleName'];
$gameTitle = $gameData['Title'];

$gameIcon = $gameData['ImageIcon'];

$gamesPlayedWithAchievements = [];
$numGamesPlayedWithAchievements = 0;

$numGamesPlayed = getUsersGameList($user, $userGamesList);

foreach ($userGamesList as $nextGameID => $nextGameData) {
    $nextGameTitle = $nextGameData['Title'];
    $nextConsoleName = $nextGameData['ConsoleName'];

    $numAchieved = $nextGameData['NumAchieved'];
    $numPossibleAchievements = $nextGameData['NumAchievements'] ?? 0;
    $gamesPlayedWithAchievements[$nextGameID] = "$nextGameTitle ($nextConsoleName) ($numAchieved / $numPossibleAchievements won)";
}

asort($gamesPlayedWithAchievements);

// Quickly calculate earned/potential
$totalEarned = 0;
$totalPossible = 0;
$numEarned = 0;
if (isset($achievementData)) {
    foreach ($achievementData as &$achievement) {
        /**
         * Some orphaned unlocks might be still around
         */
        $totalPossible += ($achievement['Points'] ?? 0);
        if (isset($achievement['DateAwarded'])) {
            $numEarned++;
            $totalEarned += $achievement['Points'];
        }
    }
}

sanitize_outputs(
    $gameTitle,
    $consoleName,
    $gameIcon,
    $user,
);

RenderContentStart("Game Compare");
?>
<div id="mainpage">
    <div id="leftcontainer">
        <div id="gamecompare">
            <?php
            echo "<div class='navpath'>";
            echo "<a href='/gameList.php'>All Games</a>";
            echo " &raquo; <a href='/gameList.php?c=$consoleID'>$consoleName</a>";
            echo " &raquo; <a href='/game/$gameID'>$gameTitle</a>";
            echo " &raquo; <b>Game Compare</b>";
            echo "</div>";

            echo "<h3 class='longheader'>Game Compare</h3>";

            $pctAwarded = 0;
            if ($numAchievements > 0) {
                $pctAwarded = sprintf("%01.0f", ($numEarned * 100.0 / $numAchievements));
            }

            echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 96);

            echo "<form action='/gamecompare.php'>";
            echo "<input type='hidden' name='f' value='$user2'>";
            echo "<select name='ID'>";
            foreach ($gamesPlayedWithAchievements as $nextGameID => $nextGameTitle) {
                $selected = ($nextGameID == $gameID) ? "SELECTED" : "";
                sanitize_outputs($nextGameTitle);
                echo "<option value='$nextGameID' $selected>$nextGameTitle</option>";
            }
            echo "</select>";
            echo "&nbsp;<input value='Change Game' type='submit' size='67'>";
            echo "</form>";
            echo "<br>";

            echo "There are <b>$numAchievements</b> achievements worth <b>$totalPossible</b> points.<br>";

            $iconSize = 48;

            echo "<table><tbody>";
            echo "<tr>";

            echo "<th>";
            echo "<a style='float: right' href='/user/$user'>$user</a><br>";
            echo GetUserAndTooltipDiv($user, true, null, $iconSize, "badgeimg rightfloat");
            echo "</th>";

            echo "<th><center>Achievement</center></th>";

            echo "<th>";
            echo "<a style='float: left' href='/user/$user2'>$user2</a><br>";
            echo GetUserAndTooltipDiv($user2, true, null, $iconSize);
            echo "</th>";

            echo "</tr>";

            $leftHardcoreAwardedCount = 0;
            $rightHardcoreAwardedCount = 0;
            $leftHardcoreAwardedPoints = 0;
            $rightHardcoreAwardedPoints = 0;
            $leftSoftcoreAwardedCount = 0;
            $rightSoftcoreAwardedCount = 0;
            $leftSoftcoreAwardedPoints = 0;
            $rightSoftcoreAwardedPoints = 0;
            $maxPoints = 0;

            $achIter = 0;
            foreach ($achievementData as $nextAch) {
                /**
                 * Some orphaned unlocks might be still around
                 */
                if (!isset($nextAch['ID'])) {
                    continue;
                }

                if ($achIter++ % 2 == 0) {
                    echo "<tr>";
                } else {
                    echo "<tr>";
                }

                $achID = $nextAch['ID'];
                $achTitle = $nextAch['Title'];
                $achDesc = $nextAch['Description'];
                $achPoints = $nextAch['Points'];

                sanitize_outputs($achTitle, $achDesc);

                $maxPoints += $achPoints;

                $badgeName = $nextAch['BadgeName'];

                $awardedLeft = $nextAch['DateEarned'] ?? null;
                $awardedRight = $nextAch['DateEarnedFriend'] ?? null;
                $awardedHCLeft = $nextAch['DateEarnedHardcore'] ?? null;
                $awardedHCRight = $nextAch['DateEarnedFriendHardcore'] ?? null;

                echo "<td class='awardlocal'>";
                if (isset($awardedLeft)) {
                    if (isset($awardedHCLeft)) {
                        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, true, true, "", $iconSize, "goldimage awardLocal");
                        $leftHardcoreAwardedCount++;
                        $leftHardcoreAwardedPoints += $achPoints;

                        echo "<small class='smalldate rightfloat'>HARDCORE<br>unlocked on<br>$awardedHCLeft</small>";
                    } else {
                        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, true, true, "", $iconSize, "awardLocal");
                        $leftSoftcoreAwardedCount++;
                        $leftSoftcoreAwardedPoints += $achPoints;

                        echo "<small class='smalldate rightfloat'>unlocked on<br>$awardedLeft</small>";
                    }
                } else {
                    echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName . "_lock", true, true, "", $iconSize, "awardLocal");
                }
                echo "</td>";

                echo "<td class='comparecenter'>";
                echo "<p class='embedded'>";
                echo "<a href=\"Achievement/$achID\"><strong>$achTitle</strong></a><br>";
                echo "$achDesc<br>";
                echo "($achPoints Points)";
                echo "</p>";
                echo "</td>";

                echo "<td class='awardremote'>";
                if (isset($awardedRight)) {
                    if (isset($awardedHCRight)) {
                        echo "<div style='float:right;' >";
                        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, true, true, "", $iconSize, "goldimage awardremote");
                        echo "</div>";
                        $rightHardcoreAwardedCount++;
                        $rightHardcoreAwardedPoints += $achPoints;

                        echo "<small class='smalldate leftfloat'>HARDCORE<br>unlocked on<br>$awardedHCRight</small>";
                    } else {
                        echo "<div style='float:right;' >";
                        echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $badgeName, true, true, "", $iconSize, "awardremote");
                        echo "</div>";
                        $rightSoftcoreAwardedCount++;
                        $rightSoftcoreAwardedPoints += $achPoints;

                        echo "<small class='smalldate leftfloat'>unlocked on<br>$awardedRight</small>";
                    }
                } else {
                    echo "<div style='float:right;' >";
                    echo "<img class='awardremote' src='" . media_asset("Badge/$badgeName" . '_lock.png') . "' alt='$achTitle' align='left' width='$iconSize' height='$iconSize'>";
                    echo "</div>";
                }
                echo "</td>";

                echo "</tr>";
            }

            // Repeat user images:
            echo "<tr>";

            echo "<td>";
            echo "<div style='float:right'>";
            echo GetUserAndTooltipDiv($user, true, null, $iconSize, "badgeimg rightfloat");
            echo "</div>";
            echo "</td>";

            echo "<td></td>";

            echo "<td>";
            echo "<div>";
            echo GetUserAndTooltipDiv($user2, true, null, $iconSize);
            echo "</div>";
            echo "</td>";

            echo "</tr>";

            // Draw totals:
            echo "<tr>";
            echo "<td class='rightfloat'>";
            echo "<b>$leftHardcoreAwardedCount</b>/$numAchievements unlocked<br><b>$leftHardcoreAwardedPoints</b>/$maxPoints points";
            echo "</td>";
            echo "<td></td>";
            echo "<td class='leftfloat'>";
            echo "<b>$rightHardcoreAwardedCount</b>/$numAchievements unlocked<br><b>$rightHardcoreAwardedPoints</b>/$maxPoints points";
            echo "</td>";
            echo "</tr>";
            if ($leftSoftcoreAwardedCount > 0 || $rightSoftcoreAwardedCount > 0) {
                echo "<tr>";
                if ($leftSoftcoreAwardedCount > 0) {
                    echo "<td class='rightfloat'>";
                    echo "<span class='softcore'<b>$leftSoftcoreAwardedCount</b>/$numAchievements unlocked<br><b>$leftSoftcoreAwardedPoints</b>/$maxPoints points</span></td>";
                } else {
                    echo "<td class='rightfloat'></td>";
                }
                echo "<td></td>";
                if ($rightSoftcoreAwardedCount > 0) {
                    echo "<td class='leftfloat'>";
                    echo "<span class='softcore'<b>$rightSoftcoreAwardedCount</b>/$numAchievements unlocked<br><b>$rightSoftcoreAwardedPoints</b>/$maxPoints points</span></td>";
                } else {
                    echo "<td class='leftfloat'></td>";
                }
                echo "</tr>";
            }

            echo "</tbody></table>";

            echo "<br><br>";
            ?>
        </div>
    </div>
    <div id="rightcontainer">
        <?php RenderGameCompare($user, $gameID, $friendScores, $totalPossible); ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
