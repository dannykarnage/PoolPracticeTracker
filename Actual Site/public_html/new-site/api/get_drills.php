<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include 'db_connect.php';

// Fetch published drills
// UPDATED: Added has_creator_video, creator_video_youtube_video_code, and creator_video_description to SELECT
$sql = "SELECT drill_id as id, name as title, featured, description, description_new, pass_fail, score, out_of, out_of_num as maxScore, out_of_pass as passThreshold, has_video, youtube_video_code, has_creator_video, creator_video_youtube_video_code, creator_video_description FROM drills WHERE published = 1";
$result = $conn->query($sql);

$drills = [];

while($row = $result->fetch_assoc()) {
    // Determine type for React app based on DB columns
    $type = 'score'; // default
    if ($row['pass_fail'] && !$row['out_of']) {
        $type = 'pass_fail';
    } elseif ($row['out_of']) {
        $type = 'score_out_of';
    }

    $featured = false;
    if ($row['featured']) {
        $featured = true;
    }

    // Description Logic: Use new column if available, otherwise strip tags from old column
    $desc = $row['description_new'];
    if (empty($desc)) {
        // Fallback: Strip HTML tags and decode entities from the old description
        $desc = strip_tags(html_entity_decode($row['description']));
    }

    $drills[] = [
        'id' => intval($row['id']),
        'title' => $row['title'],
        'description' => $desc,
        'featured' => $featured,
        'type' => $type,
        'maxScore' => $row['maxScore'] ? intval($row['maxScore']) : null,
        'passThreshold' => $row['passThreshold'] ? intval($row['passThreshold']) : null,
        // Assuming diagrams are named [id].png in /drills/diagrams/ relative to web root
        'diagramUrl' => "/drills/diagrams/" . $row['id'] . ".png",
        
        // Video Data
        'has_video' => $row['has_video'], 
        'youtube_video_code' => $row['youtube_video_code'],

        // UPDATED: Creator Video Data
        'has_creator_video' => $row['has_creator_video'],
        'creator_video_youtube_video_code' => $row['creator_video_youtube_video_code'],
        'creator_video_description' => $row['creator_video_description']
    ];
}

echo json_encode($drills);
$conn->close();
?>