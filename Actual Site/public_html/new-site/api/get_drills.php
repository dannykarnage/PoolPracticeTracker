<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

include 'db_connect.php';

// Fetch published drills
// UPDATED: Added has_video and youtube_video_code to the SELECT statement
$sql = "SELECT drill_id as id, name as title, featured, description, description_new, pass_fail, score, out_of, out_of_num as maxScore, out_of_pass as passThreshold, has_video, youtube_video_code FROM drills WHERE published = 1";
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
        
        // UPDATED: Pass video data to the frontend
        // We pass has_video exactly as it comes from DB (React handles 1 vs "1" vs true)
        'has_video' => $row['has_video'], 
        'youtube_video_code' => $row['youtube_video_code']
    ];
}

echo json_encode($drills);
$conn->close();
?>