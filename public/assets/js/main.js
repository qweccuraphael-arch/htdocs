'use strict';

// Mobile Sidebar Toggle
document.addEventListener('click', e => {
  const toggle = e.target.closest('.panel-toggle');
  if (toggle) {
    document.querySelector('.panel-sidebar')?.classList.toggle('active');
  } else if (!e.target.closest('.panel-sidebar')) {
    document.querySelector('.panel-sidebar')?.classList.remove('active');
  }
});

// Existing animations
document.querySelectorAll('.song-row,.featured-card').forEach((el,i)=>{
  el.style.cssText='opacity:0;transform:translateY(12px);transition:opacity .3s ease,transform .3s ease';
  setTimeout(()=>{el.style.opacity='1';el.style.transform='translateY(0)'},40+i*30);
});
document.querySelectorAll('.flash-msg').forEach(el=>{
  setTimeout(()=>{el.style.cssText='opacity:0;transition:opacity .4s';setTimeout(()=>el.remove(),400)},4000);
});
document.querySelectorAll('[data-confirm]').forEach(el=>{
  el.addEventListener('click',e=>{if(!confirm(el.dataset.confirm||'Are you sure?'))e.preventDefault()});
});

// Global audio player
let audio = new Audio();
let currentSong = null;
let isPlaying = false;

function playSong(id, src, title, artist) {
  if (currentSong === id) {
    if (isPlaying) {
      audio.pause();
      isPlaying = false;
    } else {
      audio.play();
      isPlaying = true;
    }
  } else {
    audio.src = src;
    document.title = `▶ ${title} – BeatWave`;
    currentSong = id;
    audio.play();
    isPlaying = true;
  }
  updatePlayer(title, artist);
}

function updatePlayer(title, artist) {
  const player = document.getElementById('global-player');
  if (player) {
    player.querySelector('.player-title').textContent = title;
    player.querySelector('.player-artist').textContent = artist || '';
    player.classList.toggle('playing', isPlaying);
  }
}

audio.addEventListener('play', () => {
  isPlaying = true;
  document.querySelectorAll('.btn-play-mini, .btn-play-global').forEach(btn => {
    btn.classList.toggle('active', parseInt(btn.dataset.songId) === currentSong);
  });
});

audio.addEventListener('pause', () => isPlaying = false);
audio.addEventListener('ended', () => {
  isPlaying = false;
  document.querySelectorAll('.btn-play-mini, .btn-play-global').forEach(btn => btn.classList.remove('active'));
  document.title = 'BeatWave – Ghana\'s Music Hub';
});

document.querySelectorAll('.btn-play-mini, .btn-play-global').forEach(btn => {
  btn.addEventListener('click', e => {
    e.preventDefault();
    const id = parseInt(btn.dataset.songId);
    const src = btn.dataset.src;
    const title = btn.closest('.song-row, .song-card, .featured-card')?.querySelector('.row-title, .song-title')?.textContent || 'Unknown';
    const artist = btn.closest('.song-row, .song-card, .featured-card')?.querySelector('.row-artist, .song-artist')?.textContent || '';
    playSong(id, src, title, artist);
  });
});

// Mini player bar (inject if not exists)
if (!document.getElementById('global-player')) {
  const playerHTML = `
    <div id="global-player" class="global-player" style="display:none;">
      <div class="player-cover" style="width:48px;height:48px;background:var(--dark-3);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:20px;margin-right:12px;">🎵</div>
      <div class="player-info" style="flex:1;min-width:0;">
        <div class="player-title" style="font-weight:500;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Select a song</div>
        <div class="player-artist" style="font-size:12px;color:var(--text-muted);">Artist</div>
      </div>
      <div class="player-controls" style="display:flex;align-items:center;gap:8px;">
        <button onclick="audio.pause()" style="background:none;border:none;color:var(--text);font-size:24px;padding:4px;">⏸</button>
        <button onclick="audio.play()" style="background:none;border:none;color:var(--gold);font-size:24px;padding:4px;">▶</button>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML('beforeend', playerHTML);
  document.getElementById('global-player').style.cssText = `
    position:fixed;bottom:0;left:0;right:0;background:var(--dark-2);border-top:1px solid var(--border);padding:8px 20px;display:flex;align-items:center;z-index:1000;
    transform:translateY(100%);transition:transform .3s ease;
  `;
}

// Show player when playing
audio.addEventListener('play', () => {
  document.getElementById('global-player').style.transform = 'translateY(0)';
});
audio.addEventListener('pause', () => {
  if (!currentSong) document.getElementById('global-player').style.transform = 'translateY(100%)';
});

