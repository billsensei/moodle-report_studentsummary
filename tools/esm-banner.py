#!/usr/bin/env python3
"""Put the Moodle boilerplate header back on the built js/esm/build files.

Core's esbuild step drops the "This file is part of Moodle" comment, so run this after `grunt esm`.
Safe to run again: files that already start with the header are left alone.
"""
import json
import pathlib

BANNER = """// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

"""

build = pathlib.Path(__file__).resolve().parent.parent / 'js' / 'esm' / 'build'
lines = BANNER.count('\n')

for js in sorted(build.glob('*.js')):
    text = js.read_text()
    if text.startswith('// This file is part of Moodle'):
        continue
    js.write_text(BANNER + text)
    # Shift the source map down by the lines just added (one ';' per empty generated line).
    smap = js.with_name(js.name + '.map')
    if smap.exists():
        data = json.loads(smap.read_text())
        data['mappings'] = ';' * lines + data['mappings']
        smap.write_text(json.dumps(data, separators=(',', ':')) + '\n')
    print('banner added to', js.relative_to(build.parent.parent.parent))
