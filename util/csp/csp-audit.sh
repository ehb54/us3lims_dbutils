#!/bin/bash
#
# csp-audit.sh -- report Content-Security-Policy violations in a USLIMS tree.
#
# Scans PHP/HTML/JS/CSS sources for constructs that a strict policy blocks:
#
#     Content-Security-Policy: default-src 'self'
#
# Static analysis of the sources, not of rendered pages, so it flags markup
# that is only emitted on some code paths -- which is the point, since those
# paths are the ones manual browser testing misses.
#
# usage: csp-audit.sh <directory> [<directory> ...]
#
# Exits 0 when clean, 1 when any violation is found, 2 on usage error.

set -u

usage() {
    # print the leading comment block (everything after the shebang that is
    # still a comment), with the leading "# " stripped
    awk 'NR==1 { next } /^#/ { sub(/^# ?/, ""); print; next } { exit }' "$0"
}

if [ $# -eq 0 ]; then
    usage
    exit 2
fi

for dir in "$@"; do
    if [ ! -d "$dir" ]; then
        echo "csp-audit: not a directory: $dir" >&2
        exit 2
    fi
done

total=0

# Vendored libraries are third-party and are loaded as external files, so their
# internals are not a CSP concern.  .git and node_modules are noise.
PRUNE=( -name .git -o -name node_modules -o -name 'jquery*.js' )

sources() {
    local ext="$1"; shift
    find "$@" \( "${PRUNE[@]}" \) -prune -o -type f -name "$ext" -print
}

report() {
    local title="$1" advice="$2" count="$3" body="$4"
    if [ "$count" -gt 0 ]; then
        echo "== $title: $count =="
        echo "$body" | sed 's|^|   |'
        echo "   -> $advice"
        echo
    fi
}


echo "CSP audit of: $*"
echo "policy assumed: default-src 'self'"
echo

# --- script-src ------------------------------------------------------------

HANDLERS='\bon(abort|blur|change|click|dblclick|error|focus|input|invalid|keydown|keypress|keyup|load|mousedown|mousemove|mouseout|mouseover|mouseup|paste|reset|resize|scroll|search|select|submit|toggle|unload|wheel)\s*='

hits=$( sources '*.php' "$@" ; sources '*.html' "$@" )
hits=$( echo "$hits" | xargs grep -nEI "$HANDLERS" 2>/dev/null \
        | grep -vE ':[0-9]+:\s*(//|#|\*)' \
        | grep -vE '\$on[a-z]+\s*=' )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "inline event handler attributes" \
       "move to a delegated listener keyed on a class or id" "$n" "$hits"

hits=$( sources '*.php' "$@" ; sources '*.html' "$@" )
hits=$( echo "$hits" | xargs grep -nI '<script' 2>/dev/null | grep -v 'src=' )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "inline <script> blocks" \
       "move the body into a .js file and load it with <script src>" "$n" "$hits"

hits=$( sources '*.php' "$@" ; sources '*.html' "$@" ; sources '*.js' "$@" )
hits=$( echo "$hits" | xargs grep -nIE "(href|action|src)\s*=\s*['\"]?javascript:" 2>/dev/null )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "javascript: URLs" \
       "replace with a class and a delegated listener" "$n" "$hits"

hits=$( sources '*.js' "$@" ; sources '*.php' "$@" )
hits=$( echo "$hits" | xargs grep -nIE "\beval\s*\(|new Function\s*\(|set(Timeout|Interval)\s*\(\s*['\"]" 2>/dev/null \
        | grep -vE ':[0-9]+:\s*(//|\*)' )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "eval / Function / string timers" \
       "rewrite without dynamic code; otherwise the policy needs 'unsafe-eval'" "$n" "$hits"

# --- style-src -------------------------------------------------------------

hits=$( sources '*.php' "$@" ; sources '*.html' "$@" )
hits=$( echo "$hits" | xargs grep -nIE "\bstyle\s*=\s*[\"']" 2>/dev/null )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "inline style= attributes" \
       "replace with a class in a stylesheet" "$n" "$hits"

hits=$( sources '*.php' "$@" ; sources '*.html' "$@" )
hits=$( echo "$hits" | xargs grep -nI '<style' 2>/dev/null )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "inline <style> blocks" \
       "move into a .css file loaded via \$css" "$n" "$hits"

# --- default-src: subresource origins --------------------------------------

hits=$( sources '*.php' "$@" ; sources '*.html' "$@" )
hits=$( echo "$hits" | xargs grep -nIE "(src|data)\s*=\s*['\"]https?://" 2>/dev/null )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "cross-origin subresource loads" \
       "self-host the asset, or add the origin to the policy" "$n" "$hits"

hits=$( sources '*.css' "$@" )
hits=$( echo "$hits" | xargs grep -nIE "@import|url\(\s*['\"]?https?:" 2>/dev/null )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "cross-origin CSS imports / url()" \
       "self-host the asset, or add the origin to the policy" "$n" "$hits"

# --- correctness checks that silently disable CSP handlers ------------------

# A PHP tag inside a .js file never executes -- the file is served statically,
# so the raw tag reaches the browser and the whole script fails to parse.  Every
# function in it is then undefined, including any the delegated CSP handlers
# call, which looks exactly like a broken CSP conversion.
hits=$( sources '*.js' "$@" )
hits=$( echo "$hits" | xargs grep -nI '<?php\|<?=' 2>/dev/null )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
total=$(( total + n ))
report "PHP tags inside .js files" \
       "pass the value through a data- attribute instead" "$n" "$hits"

# --- informational: blob:/data: need explicit policy allowances ------------

hits=$( sources '*.js' "$@" )
hits=$( echo "$hits" | xargs grep -nI 'createObjectURL' 2>/dev/null )
n=$( [ -n "$hits" ] && echo "$hits" | wc -l | tr -d ' ' || echo 0 )
if [ "$n" -gt 0 ]; then
    echo "== blob: URL construction (policy input, not a source defect): $n =="
    echo "$hits" | sed 's|^|   |'
    echo "   -> blob: does not fall under 'self'.  Any img/object/fetch that"
    echo "      consumes these needs blob: named in img-src / object-src /"
    echo "      connect-src.  Not counted as a violation."
    echo
fi

# --- verdict ---------------------------------------------------------------

if [ "$total" -eq 0 ]; then
    echo "CLEAN: no violations of default-src 'self' found."
    exit 0
fi

echo "FOUND $total violation(s)."
exit 1
