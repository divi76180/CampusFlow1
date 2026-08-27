/**
 * CampusFlow - Multilingual Text-to-Speech (TTS) Voice Reading Module
 * Supports Tamil, Hindi, Telugu, Malayalam, Kannada, and English for Parents
 */

class MultilingualTTS {
    constructor() {
        this.synth = window.speechSynthesis || null;
        this.currentUtterance = null;
        this.isPlaying = false;
        this.voices = [];
        this.onStateChange = null;

        if (this.synth) {
            this.loadVoices();
            if (speechSynthesis.onvoiceschanged !== undefined) {
                speechSynthesis.onvoiceschanged = () => this.loadVoices();
            }
        }
    }

    loadVoices() {
        if (!this.synth) return;
        this.voices = this.synth.getVoices();
    }

    /**
     * Speak leave request details aloud in preferred language
     * @param {Object} leaveData
     * @param {string} langCode - 'ta', 'hi', 'te', 'ml', 'kn', 'en'
     */
    speakLetter(leaveData, langCode = 'ta') {
        if (!this.synth) {
            alert('Text-to-speech is not supported by your browser.');
            return;
        }

        this.stop();

        const studentName = leaveData.student_name || 'மாணவர்';
        const regNo       = leaveData.register_number || '';
        const leaveType   = leaveData.leave_type || 'விடுப்பு';
        const fromDate    = leaveData.from_date || '';
        const toDate      = leaveData.to_date || '';
        const reason      = leaveData.reason || '';
        const destination = leaveData.destination_address || '';

        const speechTemplates = {
            'ta': {
                lang: 'ta-IN',
                text: `வணக்கம் பெற்றோரே. உங்கள் பிள்ளை ${studentName}, பதிவு எண் ${regNo}, ${fromDate} முதல் ${toDate} வரை ${leaveType} விடுப்பு கேட்டு விண்ணப்பித்துள்ளார். விடுப்புக்கான காரணம்: ${reason}. செல்லும் இடம்: ${destination}. விடுப்பை ஏற்க குரல் ஒப்புதல் பொத்தானை அழுத்தவும்.`
            },
            'hi': {
                lang: 'hi-IN',
                text: `नमस्ते अभिभावक। आपके बच्चे ${studentName}, रोल नंबर ${regNo}, ने ${fromDate} से ${toDate} तक ${leaveType} के लिए आवेदन किया है। छुट्टी का कारण: ${reason}। जाने का स्थान: ${destination}। कृपया वॉइस अप्रूवल बटन दबाकर अपनी सहमति दें।`
            },
            'te': {
                lang: 'te-IN',
                text: `నమస్కారం. మీ పిల్లవాడు ${studentName}, రిజిస్ట్రేషన్ నెంబర్ ${regNo}, ${fromDate} నుండి ${toDate} వరకు ${leaveType} సెలవు కోరారు. కారణం: ${reason}. గమ్యం: ${destination}. దయచేసి వాయిస్ అప్రూవల్ ద్వారా అనుమతి ఇవ్వండి.`
            },
            'ml': {
                lang: 'ml-IN',
                text: `നമസ്കാരം. നിങ്ങളുടെ കുട്ടി ${studentName}, രജിസ്ട്രേഷൻ നമ്പർ ${regNo}, ${fromDate} മുതൽ ${toDate} വരെ ${leaveType} ലീവിനായി അപേക്ഷിച്ചിരിക്കുന്നു. കാരണം: ${reason}. സ്ഥലം: ${destination}. ദയവായി വോയ്സ് അപ്രൂവൽ നൽകുക.`
            },
            'kn': {
                lang: 'kn-IN',
                text: `ನಮಸ್ಕಾರ. ನಿಮ್ಮ ಮಗು ${studentName}, ನೋಂದಣಿ ಸಂಖ್ಯೆ ${regNo}, ${fromDate} ರಿಂದ ${toDate} ವರೆಗೆ ${leaveType} ರಜೆಗಾಗಿ ಅರ್ಜಿ ಸಲ್ಲಿಸಿದ್ದಾರೆ. ಕಾರಣ: ${reason}. ಸ್ಥಳ: ${destination}. ದಯವಿಟ್ಟು ಧ್ವನಿ ಅನುಮೋದನೆ ನೀಡಿ.`
            },
            'en': {
                lang: 'en-IN',
                text: `Hello Parent. Your ward ${studentName}, registration number ${regNo}, has applied for ${leaveType} from ${fromDate} to ${toDate}. Reason for leave: ${reason}. Destination address: ${destination}. Please press the Voice Approval button to record your confirmation.`
            }
        };

        const config = speechTemplates[langCode] || speechTemplates['en'];
        this.currentUtterance = new SpeechSynthesisUtterance(config.text);
        this.currentUtterance.lang = config.lang;
        this.currentUtterance.rate = 0.95;
        this.currentUtterance.pitch = 1.0;

        // Try to match a native voice if available
        const matchedVoice = this.voices.find(v => v.lang === config.lang || v.lang.startsWith(config.lang.split('-')[0]));
        if (matchedVoice) {
            this.currentUtterance.voice = matchedVoice;
        }

        this.currentUtterance.onstart = () => {
            this.isPlaying = true;
            if (this.onStateChange) this.onStateChange(true);
        };

        this.currentUtterance.onend = () => {
            this.isPlaying = false;
            if (this.onStateChange) this.onStateChange(false);
        };

        this.currentUtterance.onerror = (err) => {
            console.error('Speech synthesis error:', err);
            this.isPlaying = false;
            if (this.onStateChange) this.onStateChange(false);
        };

        this.synth.speak(this.currentUtterance);
    }

    stop() {
        if (this.synth && this.isPlaying) {
            this.synth.cancel();
            this.isPlaying = false;
            if (this.onStateChange) this.onStateChange(false);
        }
    }
}

window.CampusTTS = new MultilingualTTS();
