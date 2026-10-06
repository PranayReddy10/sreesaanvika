Two seconds of nothing much, encoded three ways, each with a silent sound
track as a phone would write one.

Encoded here with x264, x265 and libvpx rather than kept from anywhere, and
tiny on purpose. They exist so the check on what a film holds is tested against
real files — the header an HEVC encoder actually writes — instead of against
bytes made up to look like one, which would pass a parser that was wrong.

The sound track is not decoration. A phone writes it first, and the reader
found it first and reported the codec of the sound — which caught nothing at
all. Without these files having one, that bug would still be here.

- `h264.mp4`  — what a shop should upload, and what every browser plays.
- `hevc.mp4`  — what an iPhone records by default. Refused at upload.
- `vp9.webm`  — a WebM, which is never anything a browser cannot play.
