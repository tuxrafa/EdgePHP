import type {
  BlockDefinition,
  FragmentRecord,
  PageContent,
  PageChildrenResponse,
  PageRecord,
  TemplateLayoutNode,
  TemplateRecord,
} from '../types/content';

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(path, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(init?.headers ?? {}),
    },
    ...init,
  });

  if (!response.ok) {
    let message = `HTTP ${response.status}`;
    try {
      const body = await response.json();
      message = body.message ?? message;
    } catch {
      /* ignore */
    }
    throw new Error(message);
  }

  return response.json() as Promise<T>;
}

export const api = {
  login(email: string, password: string) {
    return request<{ data: { id: number; name: string; email: string } }>(
      '/api/v1/auth/login',
      { method: 'POST', body: JSON.stringify({ email, password }) },
    );
  },
  me() {
    return request<{ data: { id: number; name: string; email: string } }>(
      '/api/v1/auth/me',
    );
  },
  logout() {
    return request<{ data: { ok: boolean } }>('/api/v1/auth/logout', {
      method: 'POST',
      body: '{}',
    });
  },
  listPages() {
    return request<{ data: PageRecord[] }>('/api/v1/pages');
  },
  listPageChildren(parent = '', limit = 200, offset = 0) {
    const params = new URLSearchParams({
      parent,
      limit: String(limit),
      offset: String(offset),
    });
    return request<PageChildrenResponse>(`/api/v1/pages/children?${params}`);
  },
  getPage(id: string) {
    return request<{ data: PageRecord }>(`/api/v1/pages/${id}`);
  },
  createPage(payload: {
    path: string;
    title: string;
    templateId?: string | null;
    sections?: PageContent['sections'];
    metadata?: PageContent['metadata'];
  }) {
    return request<{ data: PageRecord }>('/api/v1/pages', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  updatePage(
    id: string,
    payload: {
      title?: string;
      path?: string;
      templateId?: string | null;
      content?: PageContent;
      expectedRevisionId?: string | null;
      message?: string;
    },
  ) {
    return request<{ data: PageRecord }>(`/api/v1/pages/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
  },
  publishPage(id: string, message?: string) {
    return request<{ data: PageRecord & { releaseId?: string } }>(
      `/api/v1/pages/${id}/publish`,
      { method: 'POST', body: JSON.stringify({ message }) },
    );
  },
  listTemplates() {
    return request<{ data: TemplateRecord[] }>('/api/v1/templates');
  },
  getTemplate(id: string) {
    return request<{ data: TemplateRecord }>(`/api/v1/templates/${id}`);
  },
  createTemplate(payload: {
    name: string;
    slug?: string;
    description?: string;
    layout?: TemplateLayoutNode[];
  }) {
    return request<{ data: TemplateRecord }>('/api/v1/templates', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  updateTemplate(
    id: string,
    payload: {
      name?: string;
      description?: string;
      layout?: TemplateLayoutNode[];
      expectedRevisionId?: string | null;
      message?: string;
    },
  ) {
    return request<{ data: TemplateRecord }>(`/api/v1/templates/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
  },
  publishTemplate(id: string) {
    return request<{ data: TemplateRecord }>(`/api/v1/templates/${id}/publish`, {
      method: 'POST',
      body: '{}',
    });
  },
  templateUsage(id: string) {
    return request<{ data: { pages: Array<{ id: string; title: string; path: string }> } }>(
      `/api/v1/templates/${id}/usage`,
    );
  },
  listFragments() {
    return request<{ data: FragmentRecord[] }>('/api/v1/fragments');
  },
  getFragment(id: string) {
    return request<{ data: FragmentRecord }>(`/api/v1/fragments/${id}`);
  },
  createFragment(payload: {
    name: string;
    slug?: string;
    category?: string;
    sections?: PageContent['sections'];
  }) {
    return request<{ data: FragmentRecord }>('/api/v1/fragments', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },
  updateFragment(
    id: string,
    payload: {
      name?: string;
      content?: PageContent;
      expectedRevisionId?: string | null;
      message?: string;
    },
  ) {
    return request<{ data: FragmentRecord }>(`/api/v1/fragments/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(payload),
    });
  },
  publishFragment(id: string) {
    return request<{ data: FragmentRecord }>(`/api/v1/fragments/${id}/publish`, {
      method: 'POST',
      body: '{}',
    });
  },
  fragmentUsage(id: string) {
    return request<{
      data: {
        templates: Array<{ id: string; name?: string }>;
        pages: Array<{ id: string; title?: string; path?: string }>;
        indirectPageCount: number;
      };
    }>(`/api/v1/fragments/${id}/usage`);
  },
  listBlocks() {
    return request<{ data: BlockDefinition[] }>('/api/v1/blocks');
  },
};
