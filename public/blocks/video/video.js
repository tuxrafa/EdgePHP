export default function decorate(block) {
  const link = block.querySelector('a');
  if (!link) return;
  const url = link.href;
  let embed = null;
  try {
    const u = new URL(url);
    if (u.hostname.includes('youtube.com') || u.hostname.includes('youtu.be')) {
      const id = u.searchParams.get('v') || u.pathname.split('/').filter(Boolean).pop();
      if (id) {
        embed = document.createElement('iframe');
        embed.src = `https://www.youtube-nocookie.com/embed/${id}`;
        embed.title = link.textContent || 'Video';
        embed.loading = 'lazy';
        embed.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        embed.allowFullscreen = true;
      }
    }
  } catch (e) { /* ignore */ }
  if (embed) {
    const wrap = document.createElement('div');
    wrap.className = 'video-frame';
    wrap.append(embed);
    link.replaceWith(wrap);
  }
}
