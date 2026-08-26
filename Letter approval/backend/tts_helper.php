<?php
/**
 * CampusFlow - Multilingual TTS Letter Generator Helper
 */

declare(strict_types=1);

function generate_parent_tts_text(array $leave, string $language_code = 'ta'): array {
    $student_name = $leave['student_name'] ?? 'Student';
    $reg_no       = $leave['register_number'] ?? '';
    $leave_type   = $leave['leave_type'] ?? 'Leave';
    $from_date    = date('d F Y', strtotime($leave['from_date']));
    $to_date      = date('d F Y', strtotime($leave['to_date']));
    $reason       = $leave['reason'] ?? 'Personal reasons';
    $destination  = $leave['destination_address'] ?? 'Home';

    $speech_map = [
        'ta' => [
            'lang_name' => 'Tamil (தமிழ்)',
            'voice_code' => 'ta-IN',
            'speech_text' => "வணக்கம் பெற்றோரே. உங்கள் பிள்ளை {$student_name} (பதிவு எண் {$reg_no}), {$from_date} முதல் {$to_date} வரை {$leave_type} விடுப்பு கேட்டு விண்ணப்பித்துள்ளார். விடுப்புக்கான காரணம்: {$reason}. செல்லும் இடம்: {$destination}. தயவுசெய்து குரல் ஒப்புதல் பொத்தானை அழுத்தி விடுப்புக்கு ஒப்புதல் வழங்கவும்.",
            'title' => "விடுப்பு விண்ணப்ப விவரம்"
        ],
        'hi' => [
            'lang_name' => 'Hindi (हिन्दी)',
            'voice_code' => 'hi-IN',
            'speech_text' => "नमस्ते अभिभावक। आपके बच्चे {$student_name} (रजिस्ट्रेशन नंबर {$reg_no}) ने {$from_date} से {$to_date} तक {$leave_type} के लिए आवेदन किया है। छुट्टी का कारण: {$reason}। गंतव्य पता: {$destination}। कृपया वॉइस अप्रूवल बटन दबाकर अपनी सहमति प्रदान करें।",
            'title' => "अवकाश आवेदन विवरण"
        ],
        'te' => [
            'lang_name' => 'Telugu (తెలుగు)',
            'voice_code' => 'te-IN',
            'speech_text' => "నమస్కారం. మీ పిల్లవాడు {$student_name} (రిజిస్ట్రేషన్ సంఖ్య {$reg_no}), {$from_date} నుండి {$to_date} వరకు {$leave_type} సెలవు కొరకు దరఖాస్తు చేసుకున్నారు. కారణం: {$reason}. గమ్యస్థానం: {$destination}. దయచేసి వాయిస్ అప్రూవల్ బటన్ క్లిక్ చేసి ఆమోదించండి.",
            'title' => "సెలవు దరఖాస్తు వివరాలు"
        ],
        'ml' => [
            'lang_name' => 'Malayalam (മലയാളം)',
            'voice_code' => 'ml-IN',
            'speech_text' => "നമസ്കാരം. നിങ്ങളുടെ കുട്ടി {$student_name} (രജിസ്ട്രേഷൻ നമ്പർ {$reg_no}), {$from_date} മുതൽ {$to_date} വരെ {$leave_type} ലീവിനായി അപേക്ഷ നൽകിയിരിക്കുന്നു. കാരണം: {$reason}. സ്ഥലം: {$destination}. ദയവായി വോയ്സ് അപ്രൂവൽ വഴി അനുമതി നൽകുക.",
            'title' => "അവധി അപേക്ഷാ വിവരം"
        ],
        'kn' => [
            'lang_name' => 'Kannada (ಕನ್ನಡ)',
            'voice_code' => 'kn-IN',
            'speech_text' => "ನಮಸ್ಕಾರ. ನಿಮ್ಮ ಮಗು {$student_name} (ನೋಂದಣಿ ಸಂಖ್ಯೆ {$reg_no}), {$from_date} ರಿಂದ {$to_date} ರವರೆಗೆ {$leave_type} ರಜೆಗಾಗಿ ಅರ್ಜಿ ಸಲ್ಲಿಸಿದ್ದಾರೆ. ರಜೆಯ ಕಾರಣ: {$reason}. ಹೋಗುವ ಸ್ಥಳ: {$destination}. ದಯವಿಟ್ಟು ಧ್ವನಿ ಅನುಮೋದನೆ ಮೂಲಕ ಒಪ್ಪಿಗೆ ನೀಡಿ.",
            'title' => "ರಜೆ ಅರ್ಜಿಯ ವಿವರ"
        ],
        'en' => [
            'lang_name' => 'English',
            'voice_code' => 'en-IN',
            'speech_text' => "Hello Parent. Your ward {$student_name}, registration number {$reg_no}, has applied for {$leave_type} from {$from_date} to {$to_date}. Reason for leave: {$reason}. Destination address: {$destination}. Please press the Voice Approval button to record and confirm your approval.",
            'title' => "Leave Application Summary"
        ]
    ];

    $selected = $speech_map[$language_code] ?? $speech_map['en'];
    return $selected;
}
