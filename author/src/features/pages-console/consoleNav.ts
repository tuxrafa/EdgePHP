const STORAGE_KEY = 'edgephp.pagesConsole.nav';

export type ConsoleNav = {
  parents: string[];
  selectedPath: string | null;
};

export function readConsoleNav(): ConsoleNav {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY);
    if (!raw) {
      return { parents: [''], selectedPath: null };
    }
    const parsed = JSON.parse(raw) as Partial<ConsoleNav>;
    const parents = Array.isArray(parsed.parents)
      ? parsed.parents.filter((item): item is string => typeof item === 'string')
      : [];
    return {
      parents: parents.length ? parents : [''],
      selectedPath: typeof parsed.selectedPath === 'string' ? parsed.selectedPath : null,
    };
  } catch {
    return { parents: [''], selectedPath: null };
  }
}

export function writeConsoleNav(nav: ConsoleNav): void {
  sessionStorage.setItem(STORAGE_KEY, JSON.stringify(nav));
}
