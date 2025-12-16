import React, { useState, useEffect, useMemo, useRef } from 'react';
import { 
  BrowserRouter as Router, 
  Routes, 
  Route, 
  Link, 
  useLocation,
  useNavigate,
  useParams,
  useSearchParams
} from 'react-router-dom';
import { 
  Menu, X, User, LogIn, LogOut, Play, Pause, CheckCircle, 
  XCircle, ChevronRight, BarChart2, History, Trophy, Clock,
  RotateCcw, RefreshCw, Settings, Mic, MicOff, HelpCircle, UserPlus, Key
} from 'lucide-react';
import { 
  LineChart, Line, XAxis, YAxis, CartesianGrid, 
  Tooltip, ResponsiveContainer, ReferenceLine 
} from 'recharts';

/**
 * POOL PRACTICE TRACKER + SHOT CLOCK
 * Version: React Router Enabled
 * Updated: Password Reset Flow
 */

// --- Constants ---
const API_BASE = 'https://poolpracticetracker.com/new-site/api';
const SITE_BASE = 'https://poolpracticetracker.com'; 

// --- Types ---

type DrillType = 'score' | 'pass_fail' | 'score_out_of';

interface Drill {
  id: number;
  title: string;
  type: DrillType;
  description: string;
  maxScore?: number;
  passThreshold?: number;
  diagramUrl?: string;
  featured?: boolean;
}

interface DrillLog {
  id: string;
  drillId: number;
  date: string;
  score?: number;
  passed?: boolean;
}

interface UserSession {
  id: number;
  username: string;
}

// --- Mock Data (Fallback) ---
const MOCK_DRILLS: Drill[] = [
  { id: 1, title: "Stop Shot Drill (Demo)", type: 'pass_fail', description: "Shoot object ball into corner, stop cue ball dead. Repeat 10 times. Must stop dead to count.", diagramUrl: "", featured: true },
  { id: 2, title: "L-Drill (Demo)", type: 'score_out_of', maxScore: 10, description: "Run balls in L-shape without hitting rails.", diagramUrl: "", featured: true },
  { id: 3, title: "Long Potting (Demo)", type: 'score', description: "Pot object ball into corner from distance. High score wins.", diagramUrl: "", featured: true }
];

// --- Audio Engine (Shot Clock) ---
const AudioEngine = () => {
  const audioCtxRef = useRef<AudioContext | null>(null);

  const init = () => {
    if (!audioCtxRef.current) {
      const AudioContextClass = window.AudioContext || (window as any).webkitAudioContext;
      if (AudioContextClass) {
        audioCtxRef.current = new AudioContextClass();
      }
    } else if (audioCtxRef.current.state === 'suspended') {
      audioCtxRef.current.resume();
    }
  };

  const playTone = (freq: number, type: OscillatorType, duration: number, vol: number = 0.5) => {
    if (!audioCtxRef.current) init();
    const ctx = audioCtxRef.current;
    if (!ctx) return;

    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = type;
    osc.frequency.setValueAtTime(freq, ctx.currentTime);
    gain.gain.setValueAtTime(vol, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + duration);

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start();
    osc.stop(ctx.currentTime + duration);
  };

  const beepLow = () => playTone(600, 'sine', 0.2, 0.2); 
  const beepHigh = () => playTone(880, 'sine', 0.15, 0.2); 
  const warningBeep = () => playTone(600, 'triangle', 0.3, 0.2); 
  
  const foulBuzzer = () => {
    const count = 5;
    const interval = 240;
    for (let i = 0; i < count; i++) {
      setTimeout(() => { playTone(600, 'sine', 0.12, 0.12); }, i * interval);
    }
  };

  return { init, beepLow, beepHigh, warningBeep, foulBuzzer };
};

// --- Helper Components ---
const NumberSelect = ({ label, value, min, max, step, onChange, disabled = false, suffix = "s" }: any) => (
  <div className={`flex flex-col gap-1 ${disabled ? 'opacity-50' : ''}`}>
    <label className="text-gray-400 text-xs uppercase font-bold tracking-wider">{label}</label>
    <div className="flex items-center gap-2 bg-gray-800 p-2 rounded-lg border border-gray-700">
      <button className="w-8 h-8 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded text-white font-bold disabled:opacity-50" onClick={() => onChange(Math.max(min, value - step))} disabled={disabled || value <= min}>-</button>
      <span className="flex-1 text-center font-mono text-xl text-white font-bold">{value}{suffix}</span>
      <button className="w-8 h-8 flex items-center justify-center bg-gray-700 hover:bg-gray-600 rounded text-white font-bold disabled:opacity-50" onClick={() => onChange(Math.min(max, value + step))} disabled={disabled || value >= max}>+</button>
    </div>
  </div>
);

const formatTime = (seconds: number) => {
  const s = Math.ceil(seconds);
  return s < 10 ? `0${s}` : `${s}`;
};

const formatMatchTime = (totalSeconds: number) => {
  const m = Math.floor(totalSeconds / 60);
  const s = Math.floor(totalSeconds % 60);
  return `${m < 10 ? '0'+m : m}:${s < 10 ? '0'+s : s}`;
};

const getImageUrl = (url?: string) => {
  if (!url) return undefined;
  if (url.startsWith('http')) return url;
  return `${SITE_BASE}${url}`;
};

// --- SHOT CLOCK CORE LOGIC ---
const ShotClock = () => {
  // Global/Mode State
  const [screen, setScreen] = useState<'select' | 'config' | 'active'>('select');
  const [gameType, setGameType] = useState<'standard' | 'ultimate'>('standard');
  const [voiceControl, setVoiceControl] = useState(false);
  const [showHelp, setShowHelp] = useState(false);

  // Config State
  const [shotTime, setShotTime] = useState(30);
  const [extTime, setExtTime] = useState(30);
  const [graceEnabled, setGraceEnabled] = useState(true);
  const [graceTime, setGraceTime] = useState(30);
  const [warn10s, setWarn10s] = useState(false);
  const [playerMode, setPlayerMode] = useState<'single' | 'two'>('two');
  const [matchDurationMins, setMatchDurationMins] = useState(20);
  const [fastClockEnabled, setFastClockEnabled] = useState(false);
  const [fastClockTriggerMins, setFastClockTriggerMins] = useState(10);
  const [fastShotTime, setFastShotTime] = useState(15);

  // Game State
  const [isActive, setIsActive] = useState(false);
  const [shotTimeLeft, setShotTimeLeft] = useState(30);
  const [isFirstShot, setIsFirstShot] = useState(true);
  const [extensions, setExtensions] = useState({ p1: false, p2: false });
  const [foulTriggered, setFoulTriggered] = useState(false);
  const [currentShotHasExtension, setCurrentShotHasExtension] = useState(false);
  const [matchTimeLeft, setMatchTimeLeft] = useState(1200);
  const [isMatchActive, setIsMatchActive] = useState(false);
  const [isFastClockNow, setIsFastClockNow] = useState(false);

  // Refs
  const shotTimerRef = useRef<number | null>(null);
  const matchTimerRef = useRef<number | null>(null);
  const audio = useRef(AudioEngine());
  const actionsRef = useRef<{start:()=>void; pause:()=>void; reset:()=>void; extend:()=>void}>({start:()=>{},pause:()=>{},reset:()=>{},extend:()=>{}});

  // Logic Hooks
  useEffect(() => {
    if (isActive && shotTimeLeft > 0) {
      shotTimerRef.current = window.setInterval(() => {
        setShotTimeLeft((prev) => {
          const newVal = prev - 1;
          const currentInt = Math.ceil(newVal);
          const prevInt = Math.ceil(prev);
          if (currentInt !== prevInt) {
             if (warn10s && currentInt === 10) audio.current.warningBeep();
             if (currentInt <= 5 && currentInt > 0) audio.current.beepLow();
          }
          if (newVal <= 0) {
            audio.current.foulBuzzer();
            setFoulTriggered(true);
            setIsActive(false);
            return 0;
          }
          return newVal;
        });
      }, 1000);
    } else {
      if (shotTimerRef.current) clearInterval(shotTimerRef.current);
    }
    return () => { if(shotTimerRef.current) clearInterval(shotTimerRef.current); };
  }, [isActive, shotTimeLeft, warn10s]);

  useEffect(() => {
    if (gameType === 'ultimate' && isMatchActive && matchTimeLeft > 0) {
      matchTimerRef.current = window.setInterval(() => {
        setMatchTimeLeft((prev) => {
          if (prev <= 0) {
            setIsMatchActive(false);
            setIsActive(false);
            audio.current.foulBuzzer();
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    } else {
      if(matchTimerRef.current) clearInterval(matchTimerRef.current);
    }
    return () => { if(matchTimerRef.current) clearInterval(matchTimerRef.current); };
  }, [gameType, isMatchActive, matchTimeLeft]);

  // Actions
  const startGame = () => {
    audio.current.init();
    setIsFirstShot(true);
    setExtensions({ p1: false, p2: false });
    setCurrentShotHasExtension(false);
    setFoulTriggered(false);
    const startShot = graceEnabled ? (isFastClockNow ? fastShotTime : shotTime) + graceTime : (isFastClockNow ? fastShotTime : shotTime);
    setShotTimeLeft(startShot);
    if (gameType === 'ultimate') {
      setMatchTimeLeft(matchDurationMins * 60);
      setIsFastClockNow(false);
      setIsMatchActive(false);
    }
    setScreen('active');
    setIsActive(false);
    setShowHelp(false);
  };

  const toggleTimer = () => {
    if (!isActive && shotTimeLeft <= 0) return;
    const newState = !isActive;
    setIsActive(newState);
    if (gameType === 'ultimate' && matchTimeLeft > 0) setIsMatchActive(newState);
  };

  const resetShot = () => {
    setIsActive(false);
    setFoulTriggered(false);
    setCurrentShotHasExtension(false);
    if (gameType === 'ultimate' && fastClockEnabled && !isFastClockNow) {
       const timeInMins = matchTimeLeft / 60;
       if (timeInMins < fastClockTriggerMins) setIsFastClockNow(true);
    }
    if (isFirstShot) setIsFirstShot(false);
    const nextIsFast = (gameType === 'ultimate' && fastClockEnabled && (matchTimeLeft / 60 < fastClockTriggerMins)) || isFastClockNow;
    setShotTimeLeft(nextIsFast ? fastShotTime : shotTime);
  };

  const resetGame = () => {
    setIsActive(false);
    setFoulTriggered(false);
    setIsFirstShot(true);
    setExtensions({ p1: false, p2: false });
    setCurrentShotHasExtension(false);
    if (gameType === 'ultimate') setIsMatchActive(false);
    const base = isFastClockNow ? fastShotTime : shotTime;
    setShotTimeLeft(graceEnabled ? base + graceTime : base);
  };

  const useExtension = (player: 'p1' | 'p2') => {
    if (extensions[player] || currentShotHasExtension) return;
    setExtensions({ ...extensions, [player]: true });
    setCurrentShotHasExtension(true);
    setShotTimeLeft((prev) => prev + extTime);
    if (shotTimeLeft <= 0) setFoulTriggered(false); 
  };

  const fullReset = () => {
    setIsActive(false);
    setIsMatchActive(false);
    setScreen('select');
    setVoiceControl(false);
    setShowHelp(false);
  };

  // Voice Control
  useEffect(() => {
    actionsRef.current = {
      start: () => { if (!isActive && shotTimeLeft > 0) { setIsActive(true); if(gameType === 'ultimate') setIsMatchActive(true); } },
      pause: () => { setIsActive(false); if(gameType === 'ultimate') setIsMatchActive(false); },
      reset: resetShot,
      extend: () => useExtension('p1'), 
    };
  }, [isActive, shotTimeLeft, resetShot, useExtension, gameType]);

  useEffect(() => {
    if (!voiceControl || playerMode !== 'single') return;
    const SpeechRecognition = (window as any).SpeechRecognition || (window as any).webkitSpeechRecognition;
    if (!SpeechRecognition) { setVoiceControl(false); return; }
    const recognition = new SpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = false;
    recognition.lang = 'en-US';
    recognition.onresult = (event: any) => {
      const last = event.results[event.results.length - 1];
      if (last.isFinal) {
        const txt = last[0].transcript.trim().toLowerCase();
        if (txt.includes('start') || txt.includes('resume') || txt.includes('go')) actionsRef.current.start();
        else if (txt.includes('pause') || txt.includes('stop') || txt.includes('wait')) actionsRef.current.pause();
        else if (txt.includes('reset') || txt.includes('next')) actionsRef.current.reset();
        else if (txt.includes('extension') || txt.includes('extend')) actionsRef.current.extend();
      }
    };
    recognition.onend = () => { if (voiceControl) try { recognition.start(); } catch (e) {} };
    recognition.start();
    return () => { recognition.onend = null; recognition.stop(); };
  }, [voiceControl, playerMode]);

  // Help Modal
  const HelpModal = () => (
    <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 animate-in fade-in duration-200">
      <div className="bg-slate-800 w-full max-w-lg rounded-2xl shadow-2xl border border-slate-600 overflow-hidden flex flex-col max-h-[90vh]">
        <div className="bg-slate-900 p-4 border-b border-slate-700 flex items-center justify-between">
          <h3 className="text-lg font-bold text-white flex items-center gap-2"><HelpCircle size={20} className="text-emerald-500" /> Guide</h3>
          <button onClick={() => setShowHelp(false)} className="text-slate-400 hover:text-white transition-colors"> <X size={24} /> </button>
        </div>
        <div className="p-6 overflow-y-auto space-y-4 text-slate-300 text-sm">
          {screen === 'config' ? (
            gameType === 'standard' ? (
              <>
                <p><strong>Standard Mode:</strong> Classic shot clock.</p>
                <ul className="list-disc pl-5"><li><strong>Shot Time:</strong> Seconds allowed per shot.</li><li><strong>Grace Period:</strong> Extra time for the first shot after a rack.</li></ul>
              </>
            ) : (
              <>
                <p className="text-sky-400 font-bold">Ultimate Pool Match</p>
                <p>Timed match + Shot clock.</p>
                <ul className="list-disc pl-5"><li><strong>Match Time:</strong> Total match duration.</li><li><strong>Fast Clock:</strong> Automatically shortens shot time when match time is low.</li></ul>
              </>
            )
          ) : (
            <><p className="font-bold text-white">Controls</p><ul className="list-disc pl-5"><li><strong>Start/Pause:</strong> Toggles timer.</li><li><strong>Reset Shot:</strong> Use after every shot.</li><li><strong>Reset Game:</strong> Use after every rack.</li></ul></>
          )}
        </div>
      </div>
    </div>
  );

  // --- Rendering Shot Clock Screens ---
  if (screen === 'select') {
    return (
      <div className="flex flex-col items-center justify-center min-h-screen bg-slate-950 p-4 animate-in zoom-in-50 duration-300">
        <div className="text-center mb-10 space-y-2">
           <h1 className="text-4xl font-black italic tracking-tighter uppercase text-white drop-shadow-md">Pool Clocks</h1>
           <Link to="/" className="text-lg font-bold italic text-emerald-500 tracking-tight hover:text-emerald-400 hover:underline transition-colors">by PoolPracticeTracker.com</Link>
        </div>
        
        <h2 className="text-xl font-bold text-slate-500 uppercase tracking-widest mb-6">Select Mode</h2>
        
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 w-full max-w-2xl">
          <button onClick={() => { setGameType('standard'); setScreen('config'); setPlayerMode('two'); }} className="bg-slate-800 hover:bg-slate-700 border-2 border-slate-700 hover:border-emerald-500 p-8 rounded-2xl flex flex-col items-center gap-4 transition-all group shadow-xl">
             <Clock className="text-slate-500 group-hover:text-emerald-500 transition-colors" size={48} />
             <div className="text-center"><h3 className="text-xl font-bold text-white group-hover:text-emerald-400">Standard Clock</h3><p className="text-sm text-slate-400 mt-2">Classic shot timer with extensions.</p></div>
          </button>
          <button onClick={() => { setGameType('ultimate'); setScreen('config'); setPlayerMode('two'); }} className="bg-slate-800 hover:bg-slate-700 border-2 border-slate-700 hover:border-sky-500 p-8 rounded-2xl flex flex-col items-center gap-4 transition-all group shadow-xl">
             <Trophy className="text-slate-500 group-hover:text-sky-500 transition-colors" size={48} />
             <div className="text-center"><h3 className="text-xl font-bold text-white group-hover:text-sky-400">Ultimate Pool Match</h3><p className="text-sm text-slate-400 mt-2">Match + Shot timer. Fast clock logic.</p></div>
          </button>
        </div>
      </div>
    );
  }

  if (screen === 'config') {
    return (
      <div className="flex items-center justify-center min-h-screen bg-slate-950 p-4 relative">
        {showHelp && <HelpModal />}
        <div className="w-full max-w-md bg-slate-800 rounded-2xl shadow-2xl overflow-hidden border border-slate-700 flex flex-col max-h-full">
          <div className={`p-6 text-center relative ${gameType === 'ultimate' ? 'bg-sky-700' : 'bg-emerald-600'}`}>
            <button onClick={() => setScreen('select')} className="absolute top-4 left-4 text-white/70 hover:text-white p-1">← Back</button>
            <button onClick={() => setShowHelp(true)} className="absolute top-4 right-4 text-white/70 hover:text-white p-1"><HelpCircle size={20} /></button>
            <h1 className="text-2xl font-black italic tracking-tighter uppercase text-white mt-2">{gameType === 'ultimate' ? 'Ultimate Pool Match' : 'Shot Clock'}</h1>
            <p className="text-white/80 text-xs">Setup</p>
          </div>
          <div className="p-6 space-y-6 overflow-y-auto">
            {gameType === 'standard' && (
              <div className="grid grid-cols-2 gap-2 bg-slate-900 p-1 rounded-xl border border-slate-700">
                <button onClick={() => setPlayerMode('two')} className={`py-2 rounded-lg font-bold text-xs ${playerMode === 'two' ? 'bg-slate-700 text-white' : 'text-slate-500'}`}>2 Players</button>
                <button onClick={() => setPlayerMode('single')} className={`py-2 rounded-lg font-bold text-xs ${playerMode === 'single' ? 'bg-slate-700 text-white' : 'text-slate-500'}`}>1 Player</button>
              </div>
            )}
            {gameType === 'ultimate' && (
               <div className="space-y-4 border-b border-slate-700 pb-4">
                  <h3 className="text-sky-400 text-xs font-bold uppercase tracking-wider">Match Settings</h3>
                  <NumberSelect label="Match Time" value={matchDurationMins} min={10} max={60} step={5} onChange={setMatchDurationMins} suffix="m" />
                  <div className="bg-slate-900 p-3 rounded-lg space-y-3 border border-slate-700">
                    <div className="flex items-center justify-between"><label className="text-white text-sm font-bold">Fast Clock</label><input type="checkbox" checked={fastClockEnabled} onChange={(e) => setFastClockEnabled(e.target.checked)} className="w-5 h-5 accent-sky-500 cursor-pointer" /></div>
                    {fastClockEnabled && (<div className="space-y-3 pt-2"><NumberSelect label="Trigger (Mins Remaining)" value={fastClockTriggerMins} min={3} max={20} step={1} onChange={setFastClockTriggerMins} suffix="m" /><NumberSelect label="Fast Shot Time" value={fastShotTime} min={10} max={30} step={5} onChange={setFastShotTime} /></div>)}
                  </div>
               </div>
            )}
            <h3 className={`${gameType === 'ultimate' ? 'text-sky-400' : 'text-emerald-400'} text-xs font-bold uppercase tracking-wider`}>Shot Settings</h3>
            <NumberSelect label="Shot Time" value={shotTime} min={15} max={60} step={5} onChange={setShotTime} />
            <NumberSelect label="Extension Time" value={extTime} min={15} max={30} step={5} onChange={setExtTime} />
            <div className="bg-slate-750 rounded-lg p-3 border border-slate-700 flex items-center justify-between"><label className="text-gray-400 text-xs uppercase font-bold tracking-wider">Grace Period</label><input type="checkbox" checked={graceEnabled} onChange={(e) => setGraceEnabled(e.target.checked)} className={`w-5 h-5 rounded cursor-pointer ${gameType === 'ultimate' ? 'accent-sky-500' : 'accent-emerald-500'}`} /></div>
            {graceEnabled && <NumberSelect label="Added Grace Time" value={graceTime} min={15} max={30} step={5} onChange={setGraceTime} />}
            <div className="flex items-center justify-between p-3 bg-slate-750 rounded-lg border border-slate-700 cursor-pointer" onClick={() => setWarn10s(!warn10s)}><span className="text-sm font-medium text-slate-300">10-Second Warning</span><div className={`w-10 h-5 rounded-full relative transition-colors ${warn10s ? (gameType === 'ultimate' ? 'bg-sky-500' : 'bg-emerald-500') : 'bg-slate-600'}`}><div className={`absolute top-1 w-3 h-3 bg-white rounded-full transition-all ${warn10s ? 'left-6' : 'left-1'}`} /></div></div>
            <button onClick={startGame} className={`w-full text-white font-bold py-4 rounded-xl text-lg uppercase tracking-wide shadow-lg transform active:scale-95 transition-all flex items-center justify-center gap-2 ${gameType === 'ultimate' ? 'bg-sky-600 hover:bg-sky-500' : 'bg-emerald-600 hover:bg-emerald-500'}`}><Play size={20} fill="currentColor" /> Start Match</button>
          </div>
        </div>
      </div>
    );
  }

  // Active Clock Screen
  return (
    <div className="flex flex-col min-h-screen bg-slate-950 text-slate-100">
      {showHelp && <HelpModal />}
      <div className="h-14 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-4 shrink-0">
         <div className="flex items-center gap-2">
            {gameType === 'ultimate' ? <Trophy className="text-sky-500" size={20} /> : <Clock className="text-emerald-500" size={20} />}
            <span className="font-bold text-slate-200 text-sm">{gameType === 'ultimate' ? 'Ultimate Pool Match' : 'Shot Clock'}</span>
         </div>
         <div className="flex gap-2">
            {playerMode === 'single' && (
              <button onClick={() => setVoiceControl(!voiceControl)} className={`p-2 rounded-full ${voiceControl ? 'bg-red-500 text-white animate-pulse' : 'bg-slate-800 text-slate-400'}`}>
                {voiceControl ? <Mic size={18} /> : <MicOff size={18} />}
              </button>
            )}
            <button onClick={() => setShowHelp(true)} className="p-2 bg-slate-800 text-slate-400 hover:text-white rounded-full"><HelpCircle size={18} /></button>
            <button onClick={fullReset} className="p-2 bg-slate-800 text-slate-400 hover:text-red-400 rounded-full"><Settings size={18} /></button>
         </div>
      </div>
      <div className="flex-1 flex flex-col md:flex-row overflow-hidden">
        <div className="flex-1 relative flex flex-col items-center justify-center bg-slate-950 p-4">
           <div className={`absolute inset-0 opacity-10 ${foulTriggered ? 'bg-red-900' : (gameType === 'ultimate' ? 'bg-sky-900' : 'bg-emerald-900')}`} />
           {gameType === 'ultimate' && (
            <div className={`z-10 mb-4 flex items-center gap-3 px-6 py-2 rounded-full bg-slate-900 border ${matchTimeLeft < 60 ? 'border-red-500 text-red-500 animate-pulse' : 'border-slate-800 text-sky-400'}`}>
               <span className="font-mono text-4xl font-bold tracking-widest">{formatMatchTime(matchTimeLeft)}</span>
            </div>
           )}
           <div className="z-10 text-center relative">
              {isFirstShot && graceEnabled && (<div className="inline-block bg-blue-600/20 text-blue-300 border border-blue-500/30 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-4 animate-pulse">Break / First Shot</div>)}
              {voiceControl && <div className="absolute -top-8 left-1/2 -translate-x-1/2 text-xs text-red-400 animate-pulse font-bold">Listening...</div>}
              <div className={`font-mono leading-none font-black tracking-tighter transition-colors duration-100 text-[9rem] md:text-[14rem] ${foulTriggered ? 'text-red-500' : shotTimeLeft <= 5 ? 'text-red-400' : shotTimeLeft <= 10 ? 'text-yellow-400' : 'text-white'} ${isActive ? '' : 'opacity-80'}`} style={{ textShadow: "0 0 40px rgba(0,0,0,0.5)" }}>{formatTime(shotTimeLeft)}</div>
              {isFastClockNow && gameType === 'ultimate' && (<div className="text-sky-400 font-bold uppercase tracking-widest text-sm bg-sky-900/30 inline-block px-3 py-1 rounded">Fast Clock Active</div>)}
              {foulTriggered && <div className="text-red-500 font-black text-4xl uppercase tracking-widest mt-2 animate-bounce">FOUL</div>}
           </div>
        </div>
        <div className="bg-slate-900 border-t md:border-t-0 md:border-l border-slate-800 p-6 flex flex-col justify-center gap-4 md:w-80 shrink-0 z-10">
           <button onClick={toggleTimer} className={`w-full py-4 rounded-xl font-black text-xl uppercase tracking-wider shadow-lg transform active:scale-[0.98] transition-all flex items-center justify-center gap-3 ${isActive ? 'bg-yellow-500 hover:bg-yellow-400 text-yellow-950' : shotTimeLeft <= 0 ? 'bg-slate-700 text-slate-400 cursor-not-allowed' : (gameType === 'ultimate' ? 'bg-sky-500 hover:bg-sky-400' : 'bg-emerald-500 hover:bg-emerald-400') + ' text-white'}`}>{isActive ? <><Pause fill="currentColor" /> Pause</> : <><Play fill="currentColor" /> {shotTimeLeft < ((isFastClockNow ? fastShotTime : shotTime) + (isFirstShot && graceEnabled ? graceTime : 0)) ? 'Resume' : 'Start'}</>}</button>
          <div className="grid grid-cols-2 gap-3">
            <button onClick={resetShot} className="bg-slate-800 hover:bg-slate-700 border-2 border-slate-700 text-white font-bold py-3 rounded-xl flex flex-col items-center justify-center gap-1 active:bg-slate-600 transition-colors group"><RotateCcw className="group-hover:-rotate-90 transition-transform" size={20} /><span className="text-sm">Reset Shot</span></button>
            <button onClick={resetGame} className="bg-slate-800 hover:bg-slate-700 border-2 border-slate-700 text-blue-300 font-bold py-3 rounded-xl flex flex-col items-center justify-center gap-1 active:bg-slate-600 transition-colors"><RefreshCw size={20} /><span className="text-sm">Reset Game</span></button>
          </div>
          <div className="h-px bg-slate-800 w-full my-1" />
          <div className="space-y-2">
             <div className="flex justify-between text-xs text-slate-500 uppercase font-bold tracking-wider"><span>Extensions (+{extTime}s)</span></div>
             {playerMode === 'two' ? (
               <div className="grid grid-cols-2 gap-3">
                {['p1', 'p2'].map((p) => (
                   <button key={p} onClick={() => useExtension(p as 'p1'|'p2')} disabled={extensions[p as 'p1'|'p2'] || currentShotHasExtension} className={`font-bold py-3 rounded-xl shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2 text-sm ${extensions[p as 'p1'|'p2'] ? 'bg-slate-950 border border-slate-800 text-slate-600 italic cursor-not-allowed' : currentShotHasExtension ? 'bg-slate-800 text-white opacity-50 cursor-not-allowed' : (p==='p1' ? 'bg-indigo-600 hover:bg-indigo-500 text-white' : 'bg-purple-600 hover:bg-purple-500 text-white')}`}>{extensions[p as 'p1'|'p2'] ? 'Used' : <><Clock size={16} /> {p==='p1'?'P1':'P2'}</>}</button>
                ))}
               </div>
             ) : (
               <button onClick={() => useExtension('p1')} disabled={extensions.p1 || currentShotHasExtension} className={`w-full font-bold py-3 rounded-xl shadow-lg active:scale-95 transition-all flex items-center justify-center gap-2 text-sm ${extensions.p1 ? 'bg-slate-950 border border-slate-800 text-slate-600 italic cursor-not-allowed' : currentShotHasExtension ? 'bg-slate-800 text-white opacity-50 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-500 text-white'}`}>{extensions.p1 ? 'Extension Used' : <><Clock size={16} /> Use Extension</>}</button>
             )}
          </div>
        </div>
      </div>
    </div>
  );
};

// --- APP PAGES & COMPONENTS ---
const TitleUpdater = () => {
  const location = useLocation();
  useEffect(() => {
    if (location.pathname.startsWith('/shot-clock')) { document.title = 'Shot Clock by PoolPracticeTracker.com'; } 
    else { document.title = 'Pool Practice Tracker'; }
  }, [location]);
  return null;
};

const Header = ({ isMenuOpen, setIsMenuOpen, user, onLogout }: any) => {
  const navigate = useNavigate();
  const location = useLocation();
  const path = location.pathname;
  const isActive = (p: string) => path === p;

  return (
    <header className="fixed top-0 left-0 right-0 z-50 bg-slate-900 border-b border-slate-800 shadow-lg h-16">
      <div className="max-w-7xl mx-auto px-4 h-full flex items-center justify-between">
        <Link to="/" className="flex items-center gap-2 group">
          <div className="w-8 h-8 bg-emerald-500 rounded flex items-center justify-center text-slate-900 font-black italic transform -skew-x-12">P</div>
          <span className="font-bold text-slate-100 text-lg tracking-tight hidden sm:block group-hover:text-emerald-400 transition-colors">PoolPractice<span className="text-emerald-500">Tracker</span></span>
        </Link>
        <nav className="hidden md:flex items-center gap-1">
          <button onClick={() => navigate('/')} className={`px-4 py-2 rounded-lg font-bold text-sm transition-all duration-200 flex items-center gap-2 ${isActive('/') ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}>Home</button>
          <button onClick={() => navigate('/drills')} className={`px-4 py-2 rounded-lg font-bold text-sm transition-all duration-200 flex items-center gap-2 ${isActive('/drills') ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}>Drills</button>
          <button onClick={() => navigate('/shot-clock')} className={`px-4 py-2 rounded-lg font-bold text-sm transition-all duration-200 flex items-center gap-2 border border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-600/20`}>Shot Clock</button>
          {user && <button onClick={() => navigate('/profile')} className={`px-4 py-2 rounded-lg font-bold text-sm transition-all duration-200 flex items-center gap-2 ${isActive('/profile') ? 'bg-slate-800 text-emerald-400' : 'text-slate-400 hover:text-white hover:bg-slate-800'}`}><BarChart2 size={16} /> My Progress</button>}
          <div className="w-px h-6 bg-slate-700 mx-2" />
          {user ? (
            <div className="flex items-center gap-3 pl-2">
              <span className="text-xs text-slate-400 font-medium">Hi, {user.username}</span>
              <button onClick={onLogout} className="text-slate-300 hover:text-red-400 transition-colors p-2" title="Logout"><LogOut size={18} /></button>
            </div>
          ) : (
            <button onClick={() => navigate('/login')} className="text-slate-300 hover:text-white font-bold text-sm flex items-center gap-2 px-3 py-2"><LogIn size={16} /> Login</button>
          )}
        </nav>
        <button className="md:hidden p-2 text-slate-300 hover:text-white" onClick={() => setIsMenuOpen(!isMenuOpen)}>{isMenuOpen ? <X size={24} /> : <Menu size={24} />}</button>
      </div>
      {isMenuOpen && (
        <div className="md:hidden bg-slate-800 border-t border-slate-700 animate-in slide-in-from-top-2 duration-200">
          <div className="flex flex-col p-2 space-y-1">
            <button onClick={() => { navigate('/'); setIsMenuOpen(false); }} className="w-full text-left px-4 py-3 rounded-lg font-bold text-slate-300 hover:bg-slate-700">Home</button>
            <button onClick={() => { navigate('/drills'); setIsMenuOpen(false); }} className="w-full text-left px-4 py-3 rounded-lg font-bold text-slate-300 hover:bg-slate-700">Drills</button>
            <button onClick={() => { navigate('/shot-clock'); setIsMenuOpen(false); }} className="w-full text-left px-4 py-3 rounded-lg font-bold text-emerald-400 hover:bg-slate-700">Shot Clock</button>
            {user ? (
               <>
               <button onClick={() => { navigate('/profile'); setIsMenuOpen(false); }} className="w-full text-left px-4 py-3 rounded-lg font-bold text-slate-300 hover:bg-slate-700">My Progress</button>
               <button onClick={onLogout} className="w-full text-left px-4 py-3 rounded-lg font-bold text-red-400 hover:bg-slate-700">Logout</button>
               </>
            ) : (
              <button onClick={() => { navigate('/login'); setIsMenuOpen(false); }} className="w-full text-left px-4 py-3 rounded-lg font-bold text-white hover:bg-slate-700">Login</button>
            )}
          </div>
        </div>
      )}
    </header>
  );
};

const Hero = ({ onStart }: { onStart: () => void }) => (
  <div className="relative overflow-hidden rounded-3xl bg-slate-800 border border-slate-700 shadow-2xl mb-12">
    <div className="absolute inset-0 bg-gradient-to-br from-emerald-900/40 to-slate-900/90 z-10" />
    <div className="absolute inset-0 bg-[url('images/pool-practice-drill.jpeg')] bg-cover bg-center opacity-30 blur-sm scale-105" />
    <div className="relative z-20 px-8 py-20 text-center">
      <h1 className="text-4xl md:text-6xl font-black text-white mb-6 tracking-tight drop-shadow-lg">Master the <span className="text-emerald-400 italic">Game</span></h1>
      <p className="text-lg md:text-xl text-slate-300 max-w-2xl mx-auto mb-8 leading-relaxed">Track your progress, visualize your improvement, and compete with yourself using our pro-level drills and analytics.</p>
      <div className="flex flex-col sm:flex-row gap-4 justify-center">
        <button onClick={onStart} className="px-8 py-4 bg-emerald-500 hover:bg-emerald-400 text-white rounded-xl font-bold text-lg shadow-lg shadow-emerald-900/20 transform transition hover:-translate-y-1 flex items-center justify-center gap-2"><Play fill="currentColor" size={20} /> Start Practicing</button>
      </div>
    </div>
  </div>
);

const DrillCard = ({ drill, onClick }: { drill: Drill, onClick: () => void }) => (
  <div onClick={onClick} className="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden hover:border-emerald-500/50 hover:shadow-xl hover:shadow-emerald-900/10 transition-all cursor-pointer group flex flex-col h-full">
    <div className="h-40 bg-slate-900 relative overflow-hidden flex items-center justify-center bg-black">
      {drill.diagramUrl ? <img src={getImageUrl(drill.diagramUrl)} className="w-full h-full object-contain p-2" alt={drill.title} /> : <Trophy size={64} className="text-slate-700" />}
      <div className="absolute top-3 right-3 bg-slate-900/80 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-emerald-400 border border-slate-700 uppercase tracking-wider">{drill.type.replace('_', ' ')}</div>
    </div>
    <div className="p-5 flex-1 flex flex-col">
      <h3 className="text-xl font-bold text-white mb-2 group-hover:text-emerald-400 transition-colors">{drill.title}</h3>
      <p className="text-slate-400 text-sm line-clamp-3 mb-4 flex-1">{drill.description}</p>
      <div className="mt-auto flex items-center text-emerald-500 text-sm font-bold">View Drill <ChevronRight size={16} className="ml-1 group-hover:translate-x-1 transition-transform" /></div>
    </div>
  </div>
);

const DrillDetail = ({ drill, onBack, onLog, user }: { drill: Drill, onBack: () => void, onLog: (res: any) => void, user: UserSession | null }) => {
  const [score, setScore] = useState<string>('');
  const [passed, setPassed] = useState<boolean | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user) { alert("Please login to save results."); return; }
    setSubmitting(true);
    try {
      const response = await fetch(`${API_BASE}/log_score.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ user_id: user.id, drillId: drill.id, score: score ? parseInt(score) : undefined, passed: passed }) });
      const result = await response.json();
      if (result.success) {
        onLog({ id: result.id, drillId: drill.id, date: new Date().toISOString(), score: score ? parseInt(score) : undefined, passed });
        alert("Score Logged Successfully!");
        onBack();
      } else { alert("Error logging score: " + result.message); }
    } catch (err) { 
        console.error(err); 
        alert("Network error. Please try again."); 
    } finally { setSubmitting(false); }
  };

  return (
    <div className="animate-in fade-in slide-in-from-bottom-4 duration-300">
      <button onClick={onBack} className="mb-6 flex items-center text-slate-400 hover:text-white font-bold text-sm transition-colors">← Back to Drills</button>
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div className="space-y-6">
          <div className="bg-slate-800 rounded-2xl border border-slate-700 overflow-hidden shadow-xl aspect-video relative group flex items-center justify-center bg-black">
             {drill.diagramUrl ? <img src={getImageUrl(drill.diagramUrl)} alt={drill.title} className="w-full h-full object-contain" /> : <Trophy size={64} className="text-slate-700" />}
          </div>
          <div>
            <h2 className="text-3xl font-black text-white mb-4">{drill.title}</h2>
            <div className="bg-slate-800/50 p-6 rounded-xl border border-slate-700 text-slate-300 leading-relaxed text-sm whitespace-pre-wrap"><h4 className="text-emerald-400 font-bold uppercase tracking-wider text-xs mb-2">Instructions</h4>{drill.description}</div>
          </div>
        </div>
        <div className="lg:pl-8">
          <div className="bg-slate-800 p-8 rounded-2xl border border-slate-700 shadow-xl sticky top-24">
            <h3 className="text-xl font-bold text-white mb-6 flex items-center gap-2"><History className="text-emerald-500" /> Log Result</h3>
            {!user ? (
              <div className="text-center py-8">
                <p className="text-slate-400 mb-4">You must be logged in to track your progress.</p>
                <div className="px-6 py-2 bg-slate-700 text-white rounded-lg font-bold">Please Login</div>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="space-y-6">
                {drill.type === 'pass_fail' && (
                  <div className="grid grid-cols-2 gap-4">
                    <button type="button" onClick={() => setPassed(false)} className={`p-4 rounded-xl border-2 font-bold flex flex-col items-center gap-2 transition-all ${passed === false ? 'bg-red-500/20 border-red-500 text-red-400' : 'bg-slate-900 border-slate-700 text-slate-500 hover:border-slate-500'}`}><XCircle size={32} /> Failed</button>
                    <button type="button" onClick={() => setPassed(true)} className={`p-4 rounded-xl border-2 font-bold flex flex-col items-center gap-2 transition-all ${passed === true ? 'bg-emerald-500/20 border-emerald-500 text-emerald-400' : 'bg-slate-900 border-slate-700 text-slate-500 hover:border-slate-500'}`}><CheckCircle size={32} /> Passed</button>
                  </div>
                )}
                {(drill.type === 'score' || drill.type === 'score_out_of') && (
                  <div>
                    <label className="block text-slate-400 text-sm font-bold mb-2">Enter Score {drill.maxScore ? `(out of ${drill.maxScore})` : ''}</label>
                    <input type="number" value={score} onChange={(e) => setScore(e.target.value)} className="w-full bg-slate-900 border-2 border-slate-700 rounded-xl px-4 py-3 text-white font-mono text-xl focus:border-emerald-500 focus:outline-none transition-colors" placeholder="0" max={drill.maxScore} />
                  </div>
                )}
                <button type="submit" disabled={submitting || (drill.type === 'pass_fail' && passed === null) || ((drill.type === 'score' || drill.type === 'score_out_of') && score === '')} className="w-full py-4 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl font-bold text-lg shadow-lg transition-all">{submitting ? 'Saving...' : 'Save Result'}</button>
              </form>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

const ProfileChart = ({ logs, drills, user }: { logs: DrillLog[], drills: Drill[], user: UserSession | null }) => {
  const [selectedDrillId, setSelectedDrillId] = useState<number>(0);
  useEffect(() => { if (drills.length > 0 && selectedDrillId === 0) setSelectedDrillId(drills[0].id); }, [drills, selectedDrillId]);
  const chartData = useMemo(() => {
    return logs.filter(log => log.drillId === selectedDrillId).map(log => ({ date: new Date(log.date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }), score: log.score, passed: log.passed ? 1 : 0 }));
  }, [logs, selectedDrillId]);
  const currentDrill = drills.find(d => d.id === selectedDrillId);

  if (!user) return (
    <div className="flex flex-col items-center justify-center h-64 bg-slate-800 rounded-2xl border border-slate-700 p-8 text-center">
      <User size={48} className="text-slate-600 mb-4" />
      <h3 className="text-xl font-bold text-white mb-2">Please Login</h3>
      <p className="text-slate-400">You must be logged in to view your progress.</p>
    </div>
  );

  return (
    <div className="space-y-8">
      <div className="flex flex-wrap gap-2">
        {drills.map(drill => (
          <button key={drill.id} onClick={() => setSelectedDrillId(drill.id)} className={`px-4 py-2 rounded-lg text-sm font-bold transition-colors ${selectedDrillId === drill.id ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'}`}>{drill.title}</button>
        ))}
      </div>
      <div className="bg-slate-800 p-6 rounded-2xl border border-slate-700 shadow-xl">
        <h3 className="text-white font-bold mb-6 flex items-center justify-between"><span>{currentDrill?.title} Progress</span><span className="text-xs text-slate-500 font-normal uppercase tracking-wider bg-slate-900 px-2 py-1 rounded">All Time</span></h3>
        <div className="h-64 w-full">
          {chartData.length > 0 ? (
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                <XAxis dataKey="date" stroke="#94a3b8" tick={{fontSize: 12}} tickMargin={10} />
                <YAxis stroke="#94a3b8" tick={{fontSize: 12}} domain={currentDrill?.type === 'pass_fail' ? [0, 1] : [0, 'auto']} />
                <Tooltip contentStyle={{ backgroundColor: '#0f172a', borderColor: '#334155', color: '#f8fafc' }} itemStyle={{ color: '#34d399' }} />
                <ReferenceLine y={currentDrill?.maxScore} stroke="#ef4444" strokeDasharray="3 3" />
                <Line type="monotone" dataKey={currentDrill?.type === 'pass_fail' ? 'passed' : 'score'} stroke="#10b981" strokeWidth={3} dot={{ fill: '#10b981', strokeWidth: 2 }} activeDot={{ r: 8 }} />
              </LineChart>
            </ResponsiveContainer>
          ) : ( <div className="h-full flex items-center justify-center text-slate-500 italic">No data logged for this drill yet.</div> )}
        </div>
      </div>
    </div>
  );
};

const ForgotPasswordForm = () => {
  const [username, setUsername] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setMessage('');
    setError('');

    try {
      const res = await fetch(`${API_BASE}/request_reset.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username })
      });
      const data = await res.json();
      
      if (data.success) {
        setMessage(data.message || 'If that username exists, an email has been sent.');
      } else {
        setError(data.message || 'Request failed.');
      }
    } catch (err) {
      console.error(err);
      setError('Network error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center h-[60vh]">
      <div className="w-full max-w-md bg-slate-800 p-8 rounded-2xl border border-slate-700 shadow-xl">
        <h2 className="text-2xl font-bold text-white mb-6 text-center">Reset Password</h2>
        {message && <div className="bg-emerald-500/20 text-emerald-400 p-3 rounded mb-4 text-sm text-center border border-emerald-500/50">{message}</div>}
        {error && <div className="bg-red-500/20 text-red-400 p-3 rounded mb-4 text-sm text-center">{error}</div>}
        
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-slate-400 text-sm font-bold mb-2">Username</label>
            <input 
              type="text" 
              value={username} 
              onChange={e => setUsername(e.target.value)} 
              className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" 
              required 
            />
          </div>
          <button type="submit" disabled={loading} className="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold transition-colors disabled:opacity-50">
            {loading ? 'Sending...' : 'Request Reset Link'}
          </button>
        </form>
        <div className="mt-6 text-center text-sm text-slate-400">
          <Link to="/login" className="text-emerald-400 hover:text-emerald-300 font-bold transition-colors hover:underline">Back to Login</Link>
        </div>
      </div>
    </div>
  );
};

const ResetPasswordForm = () => {
  const [searchParams] = useSearchParams();
  const token = searchParams.get('token');
  const username = searchParams.get('user');
  
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (newPassword !== confirmPassword) {
      setError("Passwords do not match");
      return;
    }
    setLoading(true);
    setError('');
    setMessage('');

    try {
      const res = await fetch(`${API_BASE}/reset_password.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token, username, new_password: newPassword })
      });
      const data = await res.json();
      
      if (data.success) {
        setMessage('Password reset successful! Redirecting to login...');
        setTimeout(() => navigate('/login'), 5000);
      } else {
        setError(data.message || 'Reset failed.');
      }
    } catch (err) {
      console.error(err);
      setError('Network error');
    } finally {
      setLoading(false);
    }
  };

  if (!token || !username) {
    return <div className="text-center text-red-400 mt-20">Invalid password reset link.</div>;
  }

  return (
    <div className="flex items-center justify-center h-[60vh]">
      <div className="w-full max-w-md bg-slate-800 p-8 rounded-2xl border border-slate-700 shadow-xl">
        <h2 className="text-2xl font-bold text-white mb-6 text-center flex items-center justify-center gap-2">
          <Key className="text-emerald-500" /> New Password
        </h2>
        
        {message && <div className="bg-emerald-500/20 text-emerald-400 p-3 rounded mb-4 text-sm text-center border border-emerald-500/50">{message}</div>}
        {error && <div className="bg-red-500/20 text-red-400 p-3 rounded mb-4 text-sm text-center">{error}</div>}
        
        {!message && (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-slate-400 text-sm font-bold mb-2">New Password</label>
              <input 
                type="password" 
                value={newPassword} 
                onChange={e => setNewPassword(e.target.value)} 
                className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" 
                required minLength={6} 
              />
            </div>
            <div>
              <label className="block text-slate-400 text-sm font-bold mb-2">Confirm Password</label>
              <input 
                type="password" 
                value={confirmPassword} 
                onChange={e => setConfirmPassword(e.target.value)} 
                className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" 
                required minLength={6} 
              />
            </div>
            <button type="submit" disabled={loading} className="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold transition-colors disabled:opacity-50">
              {loading ? 'Resetting...' : 'Set New Password'}
            </button>
          </form>
        )}
      </div>
    </div>
  );
};

const LoginForm = ({ onLogin }: { onLogin: (user: UserSession) => void }) => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate(); // Hook for navigation
  const location = useLocation(); // To check for query params
  const [message, setMessage] = useState(''); // Success message state

  // Check for verified query param on load
  useEffect(() => {
    const params = new URLSearchParams(location.search);
    if (params.get('verified') === 'true') {
      setMessage('Email verified successfully! Please login.');
    }
  }, [location]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const res = await fetch(`${API_BASE}/login.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ username, password }) });
      const data = await res.json();
      if (data.success) { 
        onLogin(data.user);
        navigate('/'); // Redirect to home page on success
      } else { 
        setError(data.message || 'Login failed'); 
      }
    } catch (err) { 
        console.error(err);
        // Fallback for preview/demo
        onLogin({ id: 999, username: username || "Demo User" });
        navigate('/'); // Also redirect on fallback success
    } finally { setLoading(false); }
  };

  return (
    <div className="flex items-center justify-center h-[70vh]">
      <div className="w-full max-w-md bg-slate-800 p-8 rounded-2xl border border-slate-700 shadow-xl">
        <h2 className="text-2xl font-bold text-white mb-6 text-center">Welcome Back</h2>
        
        {/* Success Message */}
        {message && <div className="bg-emerald-500/20 text-emerald-400 p-3 rounded mb-4 text-sm text-center font-bold border border-emerald-500/50">{message}</div>}
        
        {error && <div className="bg-red-500/20 text-red-400 p-3 rounded mb-4 text-sm">{error}</div>}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div><label className="block text-slate-400 text-sm font-bold mb-2">Username</label><input type="text" value={username} onChange={e => setUsername(e.target.value)} className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" /></div>
          <div><label className="block text-slate-400 text-sm font-bold mb-2">Password</label><input type="password" value={password} onChange={e => setPassword(e.target.value)} className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" /></div>
          <button type="submit" disabled={loading} className="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold transition-colors disabled:opacity-50">{loading ? 'Logging in...' : 'Login'}</button>
        </form>
        <div className="mt-6 text-center text-sm text-slate-400 space-y-2">
          <div>
            New here?{' '}
            <Link to="/register" className="text-emerald-400 hover:text-emerald-300 font-bold transition-colors hover:underline">Register now!</Link>
          </div>
          <div>
            Having Trouble?{' '}
            <Link to="/forgot-password" className="text-emerald-400 hover:text-emerald-300 font-bold transition-colors hover:underline">Click here.</Link>
          </div>
        </div>
      </div>
    </div>
  );
};

const RegisterForm = () => {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [email, setEmail] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const res = await fetch(`${API_BASE}/register.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, email, password })
      });
      const data = await res.json();
      
      if (data.success) {
        // Show alert and redirect to login, or handle message state
        alert(data.message || "Registration successful! Please check your email.");
        navigate('/login');
      } else {
        setError(data.message || 'Registration failed');
      }
    } catch (err) {
      console.error(err);
      setError('Network error');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex items-center justify-center h-[70vh]">
      <div className="w-full max-w-md bg-slate-800 p-8 rounded-2xl border border-slate-700 shadow-xl">
        <h2 className="text-2xl font-bold text-white mb-6 text-center flex items-center justify-center gap-2">
          <UserPlus className="text-emerald-500" /> Create Account
        </h2>
        {error && <div className="bg-red-500/20 text-red-400 p-3 rounded mb-4 text-sm">{error}</div>}
        <form onSubmit={handleSubmit} className="space-y-4">
          <div><label className="block text-slate-400 text-sm font-bold mb-2">Username</label><input type="text" value={username} onChange={e => setUsername(e.target.value)} className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" required minLength={3} /></div>
          <div><label className="block text-slate-400 text-sm font-bold mb-2">Email</label><input type="email" value={email} onChange={e => setEmail(e.target.value)} className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" required /></div>
          <div><label className="block text-slate-400 text-sm font-bold mb-2">Password</label><input type="password" value={password} onChange={e => setPassword(e.target.value)} className="w-full bg-slate-900 border border-slate-700 rounded-lg px-4 py-3 text-white focus:border-emerald-500 focus:outline-none" required minLength={6} /></div>
          <button type="submit" disabled={loading} className="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold transition-colors disabled:opacity-50">{loading ? 'Creating Account...' : 'Register'}</button>
        </form>
        <div className="mt-6 text-center text-sm text-slate-400">
          Already have an account?{' '}
          <Link to="/login" className="text-emerald-400 hover:text-emerald-300 font-bold transition-colors hover:underline">Login here</Link>
        </div>
      </div>
    </div>
  );
};

const HomePage = ({ drills }: { drills: Drill[] }) => {
  const navigate = useNavigate();
  // Filter and randomize featured drills
  const featuredDrills = useMemo(() => {
    const featured = drills.filter(drill => drill.featured);
    if (featured.length <= 3) return featured;
    // Shuffle and pick 3
    const shuffled = [...featured].sort(() => 0.5 - Math.random());
    return shuffled.slice(0, 3);
  }, [drills]);

  return (
    <div className="animate-in fade-in duration-500">
      <Hero onStart={() => navigate('/drills')} />
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div className="col-span-full mb-4 flex items-center justify-between"><h2 className="text-2xl font-bold text-white">Featured Drills</h2><button onClick={() => navigate('/drills')} className="text-emerald-400 text-sm font-bold hover:underline">View All</button></div>
        {featuredDrills.length > 0 ? (
          featuredDrills.map(drill => (
            <DrillCard key={drill.id} drill={drill} onClick={() => navigate(`/drills/${drill.id}`)} />
          ))
        ) : (
          <div className="col-span-3 text-slate-500 text-center py-8 italic">No featured drills found.</div>
        )}
      </div>
    </div>
  );
};

const DrillsPage = ({ drills }: { drills: Drill[] }) => {
  const navigate = useNavigate();
  return (
    <div className="animate-in fade-in duration-300">
      <div className="mb-8"><h1 className="text-3xl font-black text-white mb-2">Practice Drills</h1><p className="text-slate-400">Select a drill to view diagrams and log your progress.</p></div>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">{drills.map(drill => <DrillCard key={drill.id} drill={drill} onClick={() => navigate(`/drills/${drill.id}`)} />)}</div>
    </div>
  );
};

// Helper to extract ID from router params
const DrillWrapper = ({ drills, onLog, user }: any) => {
  const { id } = useParams();
  const drill = drills.find((d: any) => d.id === parseInt(id || '0'));
  const navigate = useNavigate();
  
  if (!drill && drills.length > 0) return <div className="text-white">Drill not found</div>;
  if (!drill) return null; // Loading state

  return <DrillDetail drill={drill} onBack={() => navigate('/drills')} onLog={onLog} user={user} />;
};

const ProfilePage = ({ logs, drills, user }: { logs: DrillLog[], drills: Drill[], user: UserSession | null }) => {
  return (
    <div className="animate-in fade-in duration-300">
      <div className="mb-8 flex items-end justify-between"><div><h1 className="text-3xl font-black text-white mb-1">My Progress</h1><p className="text-slate-400">Track your improvement over time.</p></div><div className="text-right hidden sm:block"><div className="text-2xl font-bold text-white">{logs.length}</div><div className="text-xs text-slate-500 uppercase font-bold tracking-wider">Total Sessions</div></div></div>
      <ProfileChart logs={logs} drills={drills} user={user} />
    </div>
  );
};

const Layout = ({ children, user, onLogout }: any) => {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 font-sans selection:bg-emerald-500/30 flex flex-col">
      <Header isMenuOpen={isMenuOpen} setIsMenuOpen={setIsMenuOpen} user={user} onLogout={onLogout} />
      <main className="max-w-7xl mx-auto px-4 w-full flex-grow pt-24 pb-12">
        {children}
      </main>
    </div>
  );
};

// --- MAIN APP WITH ROUTER ---

export default function App() {
  const [drills, setDrills] = useState<Drill[]>([]);
  const [logs, setLogs] = useState<DrillLog[]>([]);
  const [user, setUser] = useState<UserSession | null>(null);

  useEffect(() => {
    fetch(`${API_BASE}/get_drills.php`)
      .then(res => res.json())
      .then(data => setDrills(data))
      .catch(err => {
        console.warn("API Fetch failed (CORS/Network), falling back to mocks.", err);
        setDrills(MOCK_DRILLS);
      });
  }, []);

  useEffect(() => {
    if (user) {
      fetch(`${API_BASE}/get_history.php?user_id=${user.id}`)
        .then(res => res.json())
        .then(data => setLogs(data))
        .catch(err => {
           console.warn("API Fetch failed (CORS/Network).", err);
           setLogs([]); 
        });
    } else { setLogs([]); }
  }, [user]);

  const handleLogScore = (newLog: DrillLog) => { setLogs([...logs, newLog]); };

  return (
    <Router basename="/new-site">
      <TitleUpdater />
      <Routes>
        {/* Standalone Shot Clock Page (No Header/Footer) */}
        <Route path="/shot-clock" element={<ShotClock />} />

        {/* Main Site Pages (With Header/Footer) */}
        <Route path="/" element={<Layout user={user} onLogout={() => setUser(null)}><HomePage drills={drills} /></Layout>} />
        <Route path="/drills" element={<Layout user={user} onLogout={() => setUser(null)}><DrillsPage drills={drills} /></Layout>} />
        
        {/* Drill Detail needs a wrapper to grab ID */}
        <Route path="/drills/:id" element={
           <Layout user={user} onLogout={() => setUser(null)}>
             <DrillWrapper drills={drills} onLog={handleLogScore} user={user} />
           </Layout>
        } />
        
        <Route path="/profile" element={<Layout user={user} onLogout={() => setUser(null)}><ProfilePage logs={logs} drills={drills} user={user} /></Layout>} />
        <Route path="/login" element={<Layout user={user} onLogout={() => setUser(null)}><LoginForm onLogin={(u) => setUser(u)} /></Layout>} />
        <Route path="/register" element={<Layout user={user} onLogout={() => setUser(null)}><RegisterForm /></Layout>} />
        <Route path="/forgot-password" element={<Layout user={user} onLogout={() => setUser(null)}><ForgotPasswordForm /></Layout>} />
        <Route path="/reset-password" element={<Layout user={user} onLogout={() => setUser(null)}><ResetPasswordForm /></Layout>} />
      </Routes>
    </Router>
  );
}