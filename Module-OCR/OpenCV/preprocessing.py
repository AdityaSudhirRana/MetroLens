"""
OpenCV Preprocessing Module for Kardashev-6 (SIH26034)

Provides modular, configurable image preprocessing functions tailored for OCR on 
packaged commodity labels. Preserves original images and handles coordinate scaling metadata.
"""

from dataclasses import dataclass, field
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple, Union
import cv2
import numpy as np


@dataclass
class ScaleMetadata:
    """Stores scaling metadata to translate coordinates between processed and original images."""
    orig_width: int
    orig_height: int
    processed_width: int
    processed_height: int
    scale_x: float
    scale_y: float

    def to_dict(self) -> Dict[str, Any]:
        return {
            "orig_width": self.orig_width,
            "orig_height": self.orig_height,
            "processed_width": self.processed_width,
            "processed_height": self.processed_height,
            "scale_x": self.scale_x,
            "scale_y": self.scale_y,
        }


@dataclass
class PreprocessResult:
    """Holds output of preprocessing pipeline including original, processed image, and scaling metadata."""
    original_image: np.ndarray
    processed_image: np.ndarray
    scale_metadata: ScaleMetadata
    applied_steps: List[str] = field(default_factory=list)


@dataclass
class PreprocessingConfig:
    """Configuration options for modular image preprocessing pipeline."""
    max_dim: Optional[int] = 1600
    target_width: Optional[int] = None
    target_height: Optional[int] = None
    grayscale: bool = True
    denoise_method: Optional[str] = "gaussian"  # 'gaussian', 'median', 'bilateral', None
    denoise_kernel: int = 3
    contrast_method: Optional[str] = "clahe"    # 'clahe', 'equalize', None
    clahe_clip_limit: float = 2.0
    clahe_grid_size: Tuple[int, int] = (8, 8)
    sharpen_strength: float = 0.5
    rotate_angle: int = 0                       # 0, 90, 180, 270
    quad_points: Optional[np.ndarray] = None    # 4 corner points for perspective correction


def load_image(source: Union[str, Path, np.ndarray, bytes]) -> np.ndarray:
    """Safely loads an image from file path, bytes, or numpy array.
    
    Raises ValueError if image loading fails or image is empty.
    """
    if isinstance(source, np.ndarray):
        if not validate_image(source, raise_on_invalid=False):
            raise ValueError("Input numpy array is invalid or empty.")
        return source.copy()

    if isinstance(source, (str, Path)):
        path_str = str(source)
        if not Path(path_str).exists():
            raise ValueError(f"Image file does not exist: {path_str}")
        image = cv2.imread(path_str)
        if image is None:
            raise ValueError(f"Could not decode image at path: {path_str}")
        return image

    if isinstance(source, bytes):
        nparr = np.frombuffer(source, np.uint8)
        image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
        if image is None:
            raise ValueError("Could not decode image from bytes.")
        return image

    raise ValueError(f"Unsupported image source type: {type(source)}")


def validate_image(image: Any, raise_on_invalid: bool = True) -> bool:
    """Validates that input is a non-empty 2D or 3D numpy array."""
    is_valid = (
        isinstance(image, np.ndarray)
        and image.size > 0
        and len(image.shape) in (2, 3)
        and image.shape[0] > 0
        and image.shape[1] > 0
    )
    if not is_valid and raise_on_invalid:
        raise ValueError("Invalid image: must be a non-empty 2D or 3D numpy array.")
    return is_valid


def resize_image(
    image: np.ndarray,
    max_dim: Optional[int] = 1600,
    target_width: Optional[int] = None,
    target_height: Optional[int] = None,
    preserve_aspect: bool = True,
) -> Tuple[np.ndarray, ScaleMetadata]:
    """Resizes image preserving aspect ratio or fitting specific bounds.
    
    Returns (resized_image, scale_metadata).
    """
    validate_image(image)
    orig_h, orig_w = image.shape[:2]

    if max_dim is None and target_width is None and target_height is None:
        meta = ScaleMetadata(orig_w, orig_h, orig_w, orig_h, 1.0, 1.0)
        return image.copy(), meta

    new_w, new_h = orig_w, orig_h

    if max_dim is not None and (orig_w > max_dim or orig_h > max_dim):
        if orig_w >= orig_h:
            new_w = max_dim
            new_h = int(round(orig_h * (max_dim / orig_w)))
        else:
            new_h = max_dim
            new_w = int(round(orig_w * (max_dim / orig_h)))
    elif target_width is not None or target_height is not None:
        if preserve_aspect:
            if target_width is not None and target_height is not None:
                scale = min(target_width / orig_w, target_height / orig_h)
                new_w = int(round(orig_w * scale))
                new_h = int(round(orig_h * scale))
            elif target_width is not None:
                scale = target_width / orig_w
                new_w = target_width
                new_h = int(round(orig_h * scale))
            elif target_height is not None:
                scale = target_height / orig_h
                new_h = target_height
                new_w = int(round(orig_w * scale))
        else:
            new_w = target_width if target_width else orig_w
            new_h = target_height if target_height else orig_h

    new_w = max(1, new_w)
    new_h = max(1, new_h)

    if (new_w, new_h) == (orig_w, orig_h):
        resized = image.copy()
    else:
        interpolation = cv2.INTER_AREA if (new_w < orig_w or new_h < orig_h) else cv2.INTER_CUBIC
        resized = cv2.resize(image, (new_w, new_h), interpolation=interpolation)

    scale_x = new_w / orig_w
    scale_y = new_h / orig_h
    meta = ScaleMetadata(orig_w, orig_h, new_w, new_h, scale_x, scale_y)

    return resized, meta


def convert_to_grayscale(image: np.ndarray) -> np.ndarray:
    """Converts 3-channel (BGR) or 4-channel (BGRA) image to 1-channel grayscale."""
    validate_image(image)
    if len(image.shape) == 2:
        return image.copy()

    channels = image.shape[2]
    if channels == 3:
        return cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    elif channels == 4:
        return cv2.cvtColor(image, cv2.COLOR_BGRA2GRAY)
    else:
        raise ValueError(f"Unsupported number of channels for grayscale conversion: {channels}")


def denoise_image(image: np.ndarray, method: str = "gaussian", kernel_size: int = 3) -> np.ndarray:
    """Applies denoising filter to image ('gaussian', 'median', 'bilateral')."""
    validate_image(image)
    if not method:
        return image.copy()

    k_size = kernel_size if kernel_size % 2 == 1 else kernel_size + 1
    method_lower = method.lower()

    if method_lower == "gaussian":
        return cv2.GaussianBlur(image, (k_size, k_size), 0)
    elif method_lower == "median":
        return cv2.medianBlur(image, k_size)
    elif method_lower == "bilateral":
        return cv2.bilateralFilter(image, d=k_size, sigmaColor=75, sigmaSpace=75)
    else:
        raise ValueError(f"Unknown denoising method '{method}'. Options: 'gaussian', 'median', 'bilateral'")


def enhance_contrast(
    image: np.ndarray,
    method: str = "clahe",
    clip_limit: float = 2.0,
    tile_grid_size: Tuple[int, int] = (8, 8),
) -> np.ndarray:
    """Enhances image contrast using CLAHE or histogram equalization."""
    validate_image(image)
    if not method:
        return image.copy()

    gray = convert_to_grayscale(image) if len(image.shape) == 3 else image.copy()
    method_lower = method.lower()

    if method_lower == "clahe":
        clahe = cv2.createCLAHE(clipLimit=clip_limit, tileGridSize=tile_grid_size)
        return clahe.apply(gray)
    elif method_lower == "equalize":
        return cv2.equalizeHist(gray)
    else:
        raise ValueError(f"Unknown contrast enhancement method '{method}'. Options: 'clahe', 'equalize'")


def sharpen_image(image: np.ndarray, strength: float = 0.5) -> np.ndarray:
    """Sharpens image using unsharp mask / kernel filter."""
    validate_image(image)
    if strength <= 0:
        return image.copy()

    blurred = cv2.GaussianBlur(image, (0, 0), sigmaX=3)
    sharpened = cv2.addWeighted(image, 1.0 + strength, blurred, -strength, 0)
    return np.clip(sharpened, 0, 255).astype(np.uint8)


def orient_image(image: np.ndarray, angle: int = 0) -> np.ndarray:
    """Rotates image by 0, 90, 180, or 270 degrees clockwise."""
    validate_image(image)
    angle = angle % 360
    if angle == 0:
        return image.copy()
    elif angle == 90:
        return cv2.rotate(image, cv2.ROTATE_90_CLOCKWISE)
    elif angle == 180:
        return cv2.rotate(image, cv2.ROTATE_180)
    elif angle == 270:
        return cv2.rotate(image, cv2.ROTATE_90_COUNTERCLOCKWISE)
    else:
        raise ValueError("Orientation angle must be a multiple of 90 degrees (0, 90, 180, 270).")


def auto_orient_image(image: np.ndarray, ocr_func=None) -> Tuple[np.ndarray, int]:
    """
    Evaluates 0, 90, 180, 270 degree rotations using lightweight OCR text score
    to automatically orient product package images right-side up.
    
    Returns (oriented_image, best_angle).
    """
    validate_image(image)
    if ocr_func is None:
        return image.copy(), 0

    best_angle = 0
    best_score = -1.0
    best_img = image.copy()

    for angle in [0, 90, 180, 270]:
        rotated = orient_image(image, angle)
        try:
            results = ocr_func(rotated)
            # Score = sum of (len(text) * confidence) for words with len >= 3
            score = sum(
                len(str(text).strip()) * float(conf)
                for _, text, conf in results
                if float(conf) > 0.2 and len(str(text).strip()) >= 3
            )
        except Exception:
            score = 0.0

        if score > best_score:
            best_score = score
            best_angle = angle
            best_img = rotated

    return best_img, best_angle



def correct_perspective(image: np.ndarray, quad_points: np.ndarray) -> np.ndarray:
    """Applies 4-point perspective warp transformation."""
    validate_image(image)
    pts = np.array(quad_points, dtype="float32")
    if pts.shape != (4, 2):
        raise ValueError("quad_points must be an array-like of 4 coordinate pairs [[x1,y1], [x2,y2], [x3,y3], [x4,y4]].")

    rect = np.zeros((4, 2), dtype="float32")
    s = pts.sum(axis=1)
    rect[0] = pts[np.argmin(s)]
    rect[2] = pts[np.argmax(s)]

    diff = np.diff(pts, axis=1)
    rect[1] = pts[np.argmin(diff)]
    rect[3] = pts[np.argmax(diff)]

    (tl, tr, br, bl) = rect
    width_A = np.sqrt(((br[0] - bl[0]) ** 2) + ((br[1] - bl[1]) ** 2))
    width_B = np.sqrt(((tr[0] - tl[0]) ** 2) + ((tr[1] - tl[1]) ** 2))
    max_width = max(int(width_A), int(width_B))

    height_A = np.sqrt(((tr[0] - br[0]) ** 2) + ((tr[1] - br[1]) ** 2))
    height_B = np.sqrt(((tl[0] - bl[0]) ** 2) + ((tl[1] - bl[1]) ** 2))
    max_height = max(int(height_A), int(height_B))

    dst = np.array([
        [0, 0],
        [max_width - 1, 0],
        [max_width - 1, max_height - 1],
        [0, max_height - 1]
    ], dtype="float32")

    M = cv2.getPerspectiveTransform(rect, dst)
    return cv2.warpPerspective(image, M, (max_width, max_height))


def save_image(image: np.ndarray, output_path: Union[str, Path]) -> str:
    """Saves numpy image array to output path creating directories if needed."""
    validate_image(image)
    out_path = Path(output_path)
    out_path.parent.mkdir(parents=True, exist_ok=True)
    success = cv2.imwrite(str(out_path), image)
    if not success:
        raise IOError(f"Failed to write image to file: {output_path}")
    return str(out_path)


def map_bbox_to_original(
    bbox: List[List[float]],
    scale_metadata: ScaleMetadata
) -> List[List[float]]:
    """Maps a 4-point bounding box / polygon from processed coordinates to original image coordinates."""
    if scale_metadata.scale_x <= 0 or scale_metadata.scale_y <= 0:
        return bbox

    mapped_bbox = []
    for point in bbox:
        x_proc, y_proc = point[0], point[1]
        x_orig = float(x_proc / scale_metadata.scale_x)
        y_orig = float(y_proc / scale_metadata.scale_y)
        mapped_bbox.append([round(x_orig, 2), round(y_orig, 2)])
    return mapped_bbox


def preprocess_image_pipeline(
    source: Union[str, Path, np.ndarray, bytes],
    config: Optional[PreprocessingConfig] = None
) -> PreprocessResult:
    """Configurable pipeline taking an image and executing preprocessing steps."""
    if config is None:
        config = PreprocessingConfig()

    orig_image = load_image(source)
    applied_steps = ["load_image"]

    img = orig_image
    if config.rotate_angle != 0:
        img = orient_image(img, config.rotate_angle)
        applied_steps.append(f"orient_image({config.rotate_angle})")

    if config.quad_points is not None:
        img = correct_perspective(img, config.quad_points)
        applied_steps.append("correct_perspective")

    img, scale_meta = resize_image(
        img,
        max_dim=config.max_dim,
        target_width=config.target_width,
        target_height=config.target_height
    )
    if scale_meta.scale_x != 1.0 or scale_meta.scale_y != 1.0:
        applied_steps.append(f"resize_image({scale_meta.processed_width}x{scale_meta.processed_height})")

    if config.grayscale:
        img = convert_to_grayscale(img)
        applied_steps.append("grayscale")

    if config.denoise_method:
        img = denoise_image(img, method=config.denoise_method, kernel_size=config.denoise_kernel)
        applied_steps.append(f"denoise({config.denoise_method})")

    if config.contrast_method:
        img = enhance_contrast(
            img,
            method=config.contrast_method,
            clip_limit=config.clahe_clip_limit,
            tile_grid_size=config.clahe_grid_size
        )
        applied_steps.append(f"contrast({config.contrast_method})")

    if config.sharpen_strength > 0:
        img = sharpen_image(img, strength=config.sharpen_strength)
        applied_steps.append(f"sharpen({config.sharpen_strength})")

    return PreprocessResult(
        original_image=orig_image,
        processed_image=img,
        scale_metadata=scale_meta,
        applied_steps=applied_steps
    )


def preprocess_image(image_path: str) -> np.ndarray:
    """Legacy wrapper for backward compatibility."""
    res = preprocess_image_pipeline(image_path)
    return res.processed_image

