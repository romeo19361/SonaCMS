# Third-Party Software Notices

SonaCMS itself is released under the MIT License (see [LICENSE](LICENSE)).
It also uses the open-source components below. Each remains under its own
license; none of them is relicensed by SonaCMS's MIT License.

---

## Bundled with SonaCMS

### PHPMailer 6.10.0

- **Location:** `SonaCMS/vendor/PHPMailer/`
- **Source:** https://github.com/PHPMailer/PHPMailer
- **License:** GNU Lesser General Public License v2.1 (LGPL-2.1)
- **Full text:** [`SonaCMS/vendor/PHPMailer/LICENSE`](SonaCMS/vendor/PHPMailer/LICENSE)

PHPMailer is used unmodified to send form and backup emails over SMTP. Under
the LGPL you may replace it with any compatible version of PHPMailer.

---

## Loaded from a CDN by the admin editor

These packages are not stored in this repository. The page editor
(`SonaCMS/app/editor.php`) loads them from jsDelivr at pinned versions.

| Package | Version | License | Source |
|---------|---------|---------|--------|
| Editor.js (`@editorjs/editorjs`) | 2.31.6 | Apache-2.0 | https://github.com/codex-team/editor.js |
| `@editorjs/header` | 2.8.9 | MIT | https://github.com/editor-js/header |
| `@editorjs/list` | 2.0.9 | MIT | https://github.com/editor-js/list |
| `@editorjs/paragraph` | 2.11.7 | MIT | https://github.com/editor-js/paragraph |
| `@editorjs/quote` | 2.7.6 | MIT | https://github.com/editor-js/quote |
| `@editorjs/underline` | 1.2.1 | MIT | https://github.com/editor-js/underline |
| `@calumk/editorjs-columns` | 0.3.2 | MIT | https://github.com/calumk/editorjs-columns |

### Editor.js — Apache License 2.0

Licensed under the Apache License, Version 2.0 (the "License"); you may not
use this file except in compliance with the License. You may obtain a copy of
the License at:

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
License for the specific language governing permissions and limitations under
the License.

---

## Written for SonaCMS

The custom editor tools in `SonaCMS/vendor/*.js` (image, gallery, download,
section, map, Facebook, tile, pricing, file-link, embed, PDF, button, form,
author, alignment, code, video and emoji tools) were written for SonaCMS and
are covered by SonaCMS's MIT License.
